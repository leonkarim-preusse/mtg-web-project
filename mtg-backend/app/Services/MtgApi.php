<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MtgApi
{
    // Returns an art-only image URL when possible (Scryfall art_crop variant)
    private function pickArtUrlFromImageUrl(?string $imageUrl): ?string
    {
        if (!$imageUrl) return null;
        if (str_starts_with($imageUrl, 'https://api.scryfall.com/cards/')) {
            return $imageUrl . (str_contains($imageUrl, '?') ? '&' : '?') . 'version=art_crop';
        }
        if (preg_match('#/scryfall-cards/(small|normal|large|png|border_crop|art_crop)/#', $imageUrl)) {
            return preg_replace('#/scryfall-cards/(small|normal|large|png|border_crop|art_crop)/#', '/scryfall-cards/art_crop/', $imageUrl);
        }
        return null;
    }
    public function searchCards(array $params): array
    {
        // Normalize params
        $filtered = [];
        foreach ($params as $k => $v) {
            if ($v === null) continue;
            $s = is_array($v) ? implode(',', $v) : trim((string)$v);
            if ($s !== '') $filtered[$k] = $s;
        }
        $filtered['page'] ??= 1;
        $filtered['pageSize'] = isset($filtered['pageSize']) ? (int)$filtered['pageSize'] : 30;
        if ($filtered['pageSize'] > 100) $filtered['pageSize'] = 100;

        $key = 'mtg:cards:' . hash('sha256', http_build_query($filtered));

        // Serve from cache if present
        $cached = Cache::get($key);
        if ($cached !== null) {
            return $cached;
        }

        // Try MTG API first
        $cards = $this->fetchFromMtgApi($filtered);

        // Only cache successful, non-empty results (prevents “stuck empty”)
        if (!empty($cards)) {
            Cache::put($key, $cards, 60);
            return $cards;
        }

        // Optional fallback to Scryfall by name (for top-bar searches)
        if (!empty($filtered['name']) && config('services.mtg.fallback_scryfall', true)) {
            $fallback = $this->fallbackScryfallByName($filtered['name'], $filtered['pageSize'], (int)$filtered['page']);
            if (!empty($fallback)) {
                Cache::put($key, $fallback, 30);
            }
            return $fallback;
        }

        return [];
    }

    private function fetchFromMtgApi(array $filtered): array
    {
        $base = config('services.mtg.base', 'https://api.magicthegathering.io/v1');
        $timeout = (int) config('services.mtg.timeout', 10);

        try {
            $res = Http::retry(3, 300)
                ->timeout($timeout)
                ->withHeaders(['User-Agent' => 'MTG-Web-Project/1.0'])
                ->acceptJson()
                ->get("$base/cards", $filtered);

            if (!$res->ok()) {
                Log::warning('MTG API non-OK', ['status' => $res->status(), 'query' => $filtered]);
                return [];
            }

            $cards = $res->json('cards') ?? [];

            return array_map(function (array $c) {
                $c['imageUrl'] = $this->pickImageUrl($c);
                return $c;
            }, $cards);
        } catch (\Throwable $e) {
            Log::error('MTG API request failed', ['error' => $e->getMessage(), 'query' => $filtered]);
            return [];
        }
    }

    private function fallbackScryfallByName(string $name, int $pageSize = 30, int $page = 1): array
    {
        try {
            $res = Http::retry(2, 300)
                ->timeout((int) config('services.mtg.timeout', 10))
                ->withHeaders(['User-Agent' => 'MTG-Web-Project/1.0'])
                ->acceptJson()
                ->get('https://api.scryfall.com/cards/search', [
                    'q'     => $name,       // simple name query
                    'order' => 'name',
                    'dir'   => 'asc',
                    'page'  => $page,
                ]);

            if (!$res->ok()) {
                Log::warning('Scryfall fallback non-OK', ['status' => $res->status(), 'q' => $name]);
                return [];
            }

            $data = $res->json('data') ?? [];
            $mapped = []; 
            foreach ($data as $d) {
                // Resolve image
                $img = null; $imgArt = null;
                if (!empty($d['image_uris']['normal'])) {
                    $img = $d['image_uris']['normal'];
                    $imgArt = $d['image_uris']['art_crop'] ?? null;
                } elseif (!empty($d['card_faces'][0]['image_uris']['normal'])) {
                    $img = $d['card_faces'][0]['image_uris']['normal'];
                    $imgArt = $d['card_faces'][0]['image_uris']['art_crop'] ?? null;
                }

                // Parse types from type_line
                $typeLine = (string) ($d['type_line'] ?? '');
                $typeMain = trim(explode('—', $typeLine)[0]);
                $typesArr = array_values(array_filter(preg_split('/\s+/', strtolower($typeMain))));

                $mapped[] = [
                    'id'        => $d['id'] ?? null,
                    'name'      => $d['name'] ?? '',
                    'imageUrl'  => $img,
                    'imageArtUrl' => $imgArt,
                    'manaCost'  => $d['mana_cost'] ?? null,
                    'cmc'       => $d['cmc'] ?? null,
                    'types'     => $typesArr,
                    'rarity'    => isset($d['rarity']) ? ucfirst((string)$d['rarity']) : null,
                    'colors'    => $d['colors'] ?? [],
                    'set'       => isset($d['set']) ? strtoupper((string)$d['set']) : null,
                    'setName'   => $d['set_name'] ?? null,
                    'text'      => $d['oracle_text'] ?? null,
                    'power'     => $d['power'] ?? null,
                    'toughness' => $d['toughness'] ?? null,
                ];

                if (count($mapped) >= $pageSize) break;
            }
            return $mapped;
        } catch (\Throwable $e) {
            Log::error('Scryfall fallback failed', ['error' => $e->getMessage(), 'q' => $name]);
            return [];
        }
    }

    private function pickImageUrl(array $c): ?string
    {
        if (!empty($c['imageUrl'])) return $c['imageUrl'];
        if (!empty($c['multiverseid'])) {
            return 'https://api.scryfall.com/cards/multiverse/' . rawurlencode((string)$c['multiverseid']) . '?format=image';
        }
        if (!empty($c['set']) && !empty($c['number'])) {
            return 'https://api.scryfall.com/cards/' . strtolower($c['set']) . '/' . rawurlencode((string)$c['number']) . '?format=image';
        }
        return null;
    }

    public function getCardById(string $id): ?array
    {
        $key = 'mtg:card:' . $id;
        return \Illuminate\Support\Facades\Cache::remember($key, 3600, function () use ($id) {
            try {
                $base = config('services.mtg.base', 'https://api.magicthegathering.io/v1');
                $res = \Illuminate\Support\Facades\Http::retry(2, 300)->timeout(10)->acceptJson()->get("$base/cards/$id");
                if ($res->ok()) {
                    $c = $res->json('card') ?? null;
                    if (is_array($c)) {
                        $c['id'] = $c['id'] ?? $id;
                        $c['imageUrl'] = $this->pickImageUrl($c);
                        $c['imageArtUrl'] = $this->pickArtUrlFromImageUrl($c['imageUrl'] ?? null);
                        return $c;
                    }
                }
            } catch (\Throwable $e) {}
            try {
                $r = \Illuminate\Support\Facades\Http::retry(2, 300)->timeout(10)->acceptJson()->get("https://api.scryfall.com/cards/$id");
                if ($r->ok()) {
                    $d = $r->json();
                    if (is_array($d)) {
                        $img = $d['image_uris']['normal'] ?? ($d['card_faces'][0]['image_uris']['normal'] ?? null);
                        $typeLine = (string) ($d['type_line'] ?? '');
                        $typeMain = trim(explode('—', $typeLine)[0]);
                        $typesArr = array_values(array_filter(preg_split('/\s+/', strtolower($typeMain))));
                        return [
                            'id'        => $d['id'] ?? $id,
                            'name'      => $d['name'] ?? '',
                            'imageUrl'  => $img,
                            'manaCost'  => $d['mana_cost'] ?? null,
                            'cmc'       => $d['cmc'] ?? null,
                            'types'     => $typesArr,
                            'rarity'    => isset($d['rarity']) ? ucfirst((string)$d['rarity']) : null,
                            'colors'    => $d['colors'] ?? [],
                            'set'       => isset($d['set']) ? strtoupper((string)$d['set']) : null,
                            'setName'   => $d['set_name'] ?? null,
                            'text'      => $d['oracle_text'] ?? null,
                            'power'     => $d['power'] ?? null,
                            'toughness' => $d['toughness'] ?? null,
                        ];
                    }
                }
            } catch (\Throwable $e) {}
            return null;
        });
    }

    public function resolveCardsByIds(array $ids): array
    {
        $out = [];
        foreach ($ids as $id) {
            $c = $this->getCardById((string)$id);
            if ($c) $out[] = $c;
        }
        return $out;
    }

    // Resolve a card ID by its exact name using Scryfall's named endpoint
    public function resolveIdByExactName(string $name): ?string
    {
        $name = trim($name);
        if ($name === '') return null;
        try {
            $r = Http::retry(2, 300)
                ->timeout((int) config('services.mtg.timeout', 10))
                ->acceptJson()
                ->get('https://api.scryfall.com/cards/named', [ 'exact' => $name ]);
            if ($r->ok()) {
                $d = $r->json();
                $id = (string) ($d['id'] ?? '');
                return $id !== '' ? $id : null;
            }
        } catch (\Throwable $e) {
            \Log::warning('Scryfall named lookup failed', ['name'=>$name, 'error'=>$e->getMessage()]);
        }
        return null;
    }
}