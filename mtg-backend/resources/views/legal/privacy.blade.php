@extends('layouts.app')

@section('content')
<article class="legal">
  <h1>Datenschutzerklärung</h1>
  <p>Im Nachfolgenden informiere ich Sie darüber, welche Daten diese Anwendung verarbeitet, zu welchen Zwecken dies geschieht und welche Rechte Ihnen zustehen.</p>

  <h2>Verantwortlicher</h2>
  <p>Verantwortlich im Sinne der DSGVO ist der Betreiber dieser Website. Kontakt: <a href="mailto:{{ config('mail.from.address') }}">{{ config('mail.from.address') }}</a>. Hinweise zum Impressum finden Sie über den Link im Footer.</p>

  <h2>Kategorien verarbeiteter Daten</h2>
  <h3>Kontodaten (bei Registrierung/Anmeldung)</h3>
  <ul>
    <li>Name (optional)</li>
    <li>E‑Mail‑Adresse</li>
    <li>Passwort (wird serverseitig gehasht gespeichert, kein Klartext)</li>
  </ul>
  <h3>Sitzungs- und Cookie-Daten</h3>
  <ul>
    <li>Session‑Cookie zur Aufrechterhaltung der Anmeldung</li>
    <li>CSRF‑Cookie zum Schutz vor Cross‑Site‑Request‑Forgery</li>
    <li>Optionaler „Angemeldet bleiben“‑Cookie</li>
  </ul>
  <h3>Inhaltsdaten (nur nach Anmeldung)</h3>
  <ul>
    <li>Decks: <code>owner_id</code>, <code>name</code>, optional <code>share_token</code> und <code>share_enabled</code> (für öffentliches Teilen per Link)</li>
    <li>Deckkarten: <code>deck_id</code>, <code>mtg_card_id</code>, <code>quantity</code></li>
    <li>Favoriten: <code>user_id</code>, <code>card_id</code> (IDs externer Kartendatenbanken)</li>
  </ul>
  <h3>Nutzungs-/Technische Daten</h3>
  <ul>
    <li>Server‑Logdaten (z. B. Anfragepfade, Zeitstempel, Statuscodes; IP-Adresse)</li>
    <li>Zwischenspeicherungen von Karten‑Suchergebnissen und Einzelkarten (anwendungsintern)</li>
  </ul>

  <h2>Zwecke der Verarbeitung</h2>
  <ul>
    <li>Bereitstellung der Website und der Such-/Deck-/Favoriten‑Funktionen</li>
    <li>Authentifizierung und Sitzungsverwaltung</li>
    <li>Zwischenspeicherung zur Performance‑Optimierung</li>
    <li>Sicherheit, Fehlersuche und Stabilität des Systems</li>
  </ul>

  <h2>Rechtsgrundlagen</h2>
  <ul>
    <li>Art. 6 Abs. 1 lit. b DSGVO (Vertrag/vertragsähnliches Nutzungsverhältnis) für Registrierung/Anmeldung sowie nutzerinitiierte Funktionen (Decks, Favoriten)</li>
    <li>Art. 6 Abs. 1 lit. f DSGVO (berechtigtes Interesse) für Sicherheit, Protokollierung, Caching und effiziente Bereitstellung</li>
  </ul>

  <h2>Empfänger und Drittlandsübermittlungen</h2>
  <p>Zur Kartensuche und ‑anzeige greifen wir auf externe APIs/CDNs zurück:</p>
  <ul>
    <li>Magic: The Gathering API (<code>api.magicthegathering.io</code>) – Abruf von Kartendaten durch den Server</li>
    <li>Scryfall API/CDN (<code>api.scryfall.com</code>) – Abruf von Kartendaten/Bildern durch Server und Browser</li>
  </ul>
  <p>Bei diesen Abrufen werden technische Metadaten (z. B. IP‑Adresse, User‑Agent, Anfrageparameter wie Kartennamen) an die jeweiligen Anbieter übermittelt. Eine Übermittlung in Drittländer (z. B. USA) ist dabei möglich. Es werden keine persönlichen Kontodaten (z. B. Passwörter, E‑Mails) an diese Dienste übermittelt.</p>

  <h2>Speicherdauer</h2>
  <ul>
    <li>Session‑Daten: bis zum Logout oder Ende der Sitzung</li>
    <li>„Angemeldet bleiben“‑Cookie: bis zum Ablauf oder manuellen Logout</li>
    <li>Decks/Favoriten: bis zur Löschung durch Nutzer oder Konto‑Löschung</li>
    <li>Cache von Suchergebnissen: typischerweise 30–60 Min., Einzelkarten bis ca. 1 Stunde</li>
    <li>Server‑Logs: gemäß Serverkonfiguration (nur für Fehlersuche/Sicherheit)</li>
  </ul>

  <h2>Bereitstellungspflicht</h2>
  <p>Die Angabe von E‑Mail und Passwort ist für eine Registrierung/Anmeldung erforderlich. Ohne Anmeldung stehen nur öffentliche Funktionen, wie die Kartensuche, zur Verfügung.</p>

  <h2>Automatisierte Entscheidungsfindung</h2>
  <p>Es findet keine automatisierte Entscheidungsfindung im Sinne von Art. 22 DSGVO statt.</p>

  <h2>Ihre Rechte</h2>
  <ul>
    <li>Auskunft (Art. 15 DSGVO)</li>
    <li>Berichtigung (Art. 16 DSGVO)</li>
    <li>Löschung (Art. 17 DSGVO)</li>
    <li>Einschränkung der Verarbeitung (Art. 18 DSGVO)</li>
    <li>Datenübertragbarkeit (Art. 20 DSGVO)</li>
    <li>Widerspruch (Art. 21 DSGVO)</li>
  </ul>

  <h2>Kontakt</h2>
  <p>Bitte wenden Sie sich für Datenschutzanfragen an <a href="mailto:{{ config('mail.from.address') }}">{{ config('mail.from.address') }}</a>.</p>
</article>
@endsection
