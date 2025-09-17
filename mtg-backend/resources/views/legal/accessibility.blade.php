@extends('layouts.app')

@section('content')
<article class="legal">
  <h1>Erklärung zur Barrierefreiheit</h1>
  <p>Ich bin bemüht, meine Website im Einklang mit den geltenden gesetzlichen Bestimmungen barrierefrei zugänglich zu machen. Die folgenden Maßnahmen sind implementiert. Weitere Verbesserungen sind leider nicht absehbar.</p>

  <h2>Stand der Vereinbarkeit</h2>
  <p>Diese Website ist weitgehend mit den Anforderungen der WCAG 2.1 AA konform.</p>
  <h2>Bekannte Einschränkungen</h2>
  <ul>
    <li>Fokus‑Management in dynamischen Popovern verbesserungswürdig (z. B. durch  ESC‑Schließen, Rückführung des Fokus).</li>
    <li>Keine Live‑Regionen </li>
    <li>Kontraste einzelner Elemente werden weiter geprüft und bei Bedarf angepasst.</li>
  </ul>

  <h2>Barrierefreie Bedienung</h2>
  <ul>
    <li>"Zum Inhalt springen"‑Link für Tastaturnutzer (Skip‑Link)</li>
    <li>Deutliche Fokusrahmen </li>
    <li>Semantische Regionen (Banner, Navigation, Hauptbereich, Footer)</li>
    <li>Bedienelemente als echte Buttons/Links</li>
    <li>Zustände an Komponenten (Favoriten‑Toggle)</li>
    <li>Popover‑Schalter</li>
    <li>Alternativtexte an Kartendarstellungen</li>
    <li>Unterstützung von reduzierter Bewegung</li>
  </ul>


</article>
@endsection
