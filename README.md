# Chess Online PWA ♟️

Volledige schaak webapplicatie en Progressive Web App (PWA) ontwikkeld conform de Figma-ontwerpen en de SMART-leerdoelen (MBO Software Developer Level 3).

---

## 🚀 Overzicht van de Pagina's & Functionaliteiten

| Pagina | Bestand | Figma Referentie & Functie |
| :--- | :--- | :--- |
| **Keuzescherm** | [`php/login-aanmeld.php`](file:///c:/xampp/htdocs/Chess/php/login-aanmeld.php) | `Figma/login-aanmeld.png` – Startscherm met keuzeknoppen 'Log in' en 'Aanmelden'. |
| **Inloggen** | [`php/inlog.php`](file:///c:/xampp/htdocs/Chess/php/inlog.php) | `Figma/inlog.png` – Inlogformulier met gebruikersnaam, wachtwoord en 'Klaar' knop. |
| **Aanmelden** | [`php/aanmeld.php`](file:///c:/xampp/htdocs/Chess/php/aanmeld.php) | `Figma/aanmeld.png` – Account aanmaken met optionele profielfoto upload en live preview. |
| **Home Dashboard** | [`php/home.php`](file:///c:/xampp/htdocs/Chess/php/home.php) | `Figma/home.png` – Tijdkeuze (10m, 5m, 3m), 'Random tegenstander', vriendenlijst en wedstrijdgeschiedenis met ELO (+8 / -8). |
| **Schaakspel** | [`php/game.php`](file:///c:/xampp/htdocs/Chess/php/game.php) / `chess.php` | `Figma/chess.png` – Volledig interactief bord, timers, zetgeschiedenis in kolommen, interactieve live chat, opgeven en gelijkspel. |
| **Vrienden & Ranglijst** | [`php/vrienden.php`](file:///c:/xampp/htdocs/Chess/php/vrienden.php) | Vriendenbeheer, direct uitdagen en **ELO Ranglijst** (*"Players sorteren met punten(ELO)"*). |
| **Profiel** | [`php/profiel.php`](file:///c:/xampp/htdocs/Chess/php/profiel.php) | Spelersstatistieken (gespeeld, winst, verlies, winratio), profielfoto wijzigen en wedstrijdgeschiedenis. |
| **Uitloggen** | [`php/uitloggen.php`](file:///c:/xampp/htdocs/Chess/php/uitloggen.php) | Veilig afmelden en offline-status instellen. |

---

## 🛠️ Gebruikte Technieken (Tech Stack)

- **Backend & Database:**
  - PHP 8+ met PDO.
  - Automatische SQLite database initialisatie ([`chess.sqlite`](file:///c:/xampp/htdocs/Chess/chess.sqlite)) zonder handmatige configuratie.
  - MySQL export script inbegrepen ([`database.sql`](file:///c:/xampp/htdocs/Chess/database.sql)) voor schoolinlevering.
- **Frontend & Styling:**
  - HTML5 & Semantische structuur.
  - Custom Vanilla CSS ([`css/style.css`](file:///c:/xampp/htdocs/Chess/css/style.css)) met Figma kleurenpalet (olijf/bruin cards, houten dambord, vallende stukken overlay, responsief voor mobiel, tablet en desktop).
- **Schaak Engine & JavaScript:**
  - Volledige spelregels ([`javascript/chess-game.js`](file:///c:/xampp/htdocs/Chess/javascript/chess-game.js)): pionnen, lopers, torens, paarden, koningin, koning.
  - Rokade (korte & lange rokade) en en-passant ondersteuning.
  - Legale zetten validatie (geen ongeldige zetten, koning mag niet in schaak blijven of komen).
  - Pionnen opeten, schaak, schaakmat en pat (gelijkspel) detectie.
  - Geïntegreerde AI bot-tegenstander voor solo gameplay.
  - Web Audio geluidseffecten voor zetten en slaan.
- **PWA (Progressive Web App):**
  - Web App Manifest ([`manifest.json`](file:///c:/xampp/htdocs/Chess/manifest.json)).
  - Service Worker ([`sw.js`](file:///c:/xampp/htdocs/Chess/sw.js)) voor caching en offline functionaliteit.

---

## 💻 Hoe te openen in XAMPP

1. Start **Apache** via het XAMPP Control Panel.
2. Open je browser en surf naar:  
   👉 **`http://localhost/Chess/`** of **`http://localhost/Chess/php/index.php`**
3. Je kunt direct inloggen met een demo-account (wachtwoord voor demo-accounts: `welkom123`):
   - Gebruikersnaam: `GrootmeesterNL` (Rating: 1973)
   - Gebruikersnaam: `SchaakMeester` (Rating: 2150)
   - Gebruikersnaam: `Naam Naam` (Rating: 1928)
   - Of maak een nieuw account aan via **Aanmelden**!