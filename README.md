# Programma Lezioni

Un'applicazione web per la gestione delle lezioni tra insegnanti e studenti, facilitando la prenotazione e l'organizzazione degli incontri formativi.

## 🚀 Caratteristiche Principali

### Per gli Insegnanti
- **Gestione Lezioni**: Crea, modifica e cancella lezioni
- **Impostazione Disponibilità**: Configura i tuoi orari disponibili per le lezioni
- **Integrazione Google Calendar**: Sincronizza la tua disponibilità con Google Calendar
- **Prenotazioni**: Visualizza e gestisci le prenotazioni ricevute dagli studenti
- **Gestione Studenti**: Monitora gli studenti che seguono le tue lezioni
- **Reportistica**: Genera report sulle attività di insegnamento

### Per gli Studenti
- **Prenotazione Lezioni**: Visualizza e prenota le lezioni disponibili
- **Orari Insegnanti**: Controlla la disponibilità degli insegnanti
- **Storico Lezioni**: Visualizza lo storico delle lezioni prenotate e completate
- **Cerca Insegnante**: Trova facilmente un insegnante tramite email o parametri di ricerca

## 💻 Tecnologie Utilizzate

- **Frontend**: HTML, CSS, JavaScript
- **Backend**: PHP
- **Database**: MySQL
- **Integrazione API**: Google Calendar
- **Autenticazione**: Sistema di login personalizzato con sessioni PHP

## 🛠️ Installazione

1. Clona il repository sul tuo server web:
```bash
git clone https://github.com/tuousername/programma-lezioni.git
```

2. Importa il database dal file `DB.sql` nel tuo server MySQL:
```bash
mysql -u username -p database_name < DB.sql
```

3. Configura i parametri di connessione al database modificando il file `connessione.php`:
```php
$servername = "localhost";
$username = "tuousername";
$password = "tuapassword";
$dbname = "nome_database";
```

4. Configura il server web per puntare alla directory principale del progetto.

5. Accedi all'applicazione tramite browser all'indirizzo del tuo server web.

## 👨‍🏫 Guida Rapida

### Per gli Insegnanti:
1. Imposta i tuoi orari di disponibilità nella sezione "Disponibilità"
2. Crea nuove lezioni nella sezione "Gestisci Lezioni"
3. Monitora le prenotazioni ricevute nella sezione "Prenotazioni"
4. Visualizza le statistiche complete nella sezione "Report"

### Per gli Studenti:
1. Cerca un insegnante nella sezione "Cerca Insegnante"
2. Visualizza gli orari di disponibilità degli insegnanti nella sezione "Orari"
3. Consulta lo storico delle tue lezioni nella sezione "Storico"

## 📁 Struttura del Progetto

- `/api`: Endpoint API per le interazioni client-server
- `/front-end`: Pagine dell'interfaccia utente
- `/js`: Script JavaScript
- `/login`: Sistema di autenticazione
- `/styles`: Fogli di stile CSS
- `/error`: Pagine di errore

## 📱 Funzionalità Responsive

L'applicazione è progettata per funzionare su dispositivi di diverse dimensioni, con layout responsive che si adattano a desktop, tablet e smartphone.

## 🔒 Sicurezza

- Protezione contro SQL Injection con prepared statements
- Autenticazione basata su sessioni
- Controlli di accesso per ruoli differenti (professori e studenti)
- Validazione dei dati di input

## ⚙️ Personalizzazione

È possibile personalizzare l'aspetto dell'applicazione modificando i file CSS nella directory `/styles`.

## 🤝 Contributi

Per contribuire al progetto:
1. Forkare il repository
2. Creare un branch per la tua funzionalità (`git checkout -b feature/amazing-feature`)
3. Committare le modifiche (`git commit -m 'Aggiungi una funzionalità incredibile'`)
4. Pushare al branch (`git push origin feature/amazing-feature`)
5. Aprire una Pull Request

## 📄 Licenza

Questo progetto è distribuito con licenza [Creative Commons Attribution-NonCommercial 4.0 International (CC BY-NC 4.0)](https://creativecommons.org/licenses/by-nc/4.0/). Vedere il file [`LICENSE`](./LICENSE.md) per maggiori informazioni.


## roadmap per domani

- navbar da sistemare 
- implementare lezioni di gruppo
- professore -> report da sistemare
- tour generale per verificare il corretto funzionamento
