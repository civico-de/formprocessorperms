---
title: Form-Processor-Berechtigungen
---

# Form-Processor-Berechtigungen

Im Form Processor kann jeder Prozessor eine eigene Berechtigung für API-Aufrufe verlangen. Er prüft diese Berechtigung, meldet sie aber nie bei CiviCRM an. Auf Standalone verwirft das Speichern einer Rolle die Berechtigung dann stillschweigend, und jeder API-Aufruf des Prozessors scheitert mit „Authorization failed“. Unter Drupal fehlt die Berechtigung auf der Berechtigungsseite.

Diese Extension meldet die Berechtigung jedes Form Processors an, der eine hat. Sie erscheint in der Berechtigungsliste als „Form Processor: <Titel des Prozessors>“ und lässt sich Rollen zuweisen wie jede andere Berechtigung.

## Voraussetzungen

Form Processor und Action Provider müssen installiert sein. Die unterstützten CiviCRM- und PHP-Versionen stehen auf der Seite der Extension. Einzustellen gibt es nichts.

## Berechtigung eines Prozessors zuweisen

1. Die Berechtigung am Form Processor eintragen.
2. Die Caches leeren (Verwalten → Systemeinstellungen → Cache leeren, oder `cv flush`). Unter Drupal zusätzlich den Drupal-Cache leeren.
3. „Form Processor: <Titel des Prozessors>“ der Rolle zuweisen, die den Prozessor aufruft.

Ein API-Benutzer braucht außerdem „authenticate with api key“ und „access CiviCRM backend and API“. Diese sind unabhängig von der Berechtigung des Prozessors.

## Deinstallieren

Beim Deinstallieren meldet die Extension die Berechtigungen wieder ab. Auf Standalone verschwinden sie aus einer Rolle, sobald die Rolle das nächste Mal gespeichert wird, und API-Aufrufe dieser Prozessoren scheitern wieder.
