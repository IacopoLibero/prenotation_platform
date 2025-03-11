# Sito Programma Lezioni

Questo sistema web permette di gestire le prenotazioni delle lezioni private tra insegnanti e studenti.

## Funzionalità Principali

- **Prenotazione Lezioni**: Gli studenti possono prenotare lezioni disponibili tramite un calendario interattivo.
- **Gestione Orari**: Gli insegnanti possono impostare e gestire i propri orari disponibili per le lezioni.
- **Notifiche**: Notifiche via email per confermare le prenotazioni e ricordare gli appuntamenti.
- **Storico Lezioni**: Gli studenti possono visualizzare lo storico delle lezioni prenotate e completate.

## Tipi di Account

Il sito supporta due tipi di account: insegnante e studente, ognuno con funzionalità specifiche.

### Account Insegnante

- **Gestione Lezioni**: Creare, modificare e cancellare lezioni.
- **Settare disponibilità**: Impostare disponibilità delle lezioni nel calendario.
- **Visualizzazione Prenotazioni**: Visualizzare tutte le prenotazioni ricevute.
- **Gestione Studenti**: Visualizzare e gestire gli studenti che hanno prenotato lezioni.
- **Reportistica**: Generare report sulle lezioni effettuate, come per esempio il numero di ore.

### Account Studente

- **Prenotazione Lezioni**: Prenotare lezioni disponibili.
- **Visualizzazione Orari**: Visualizzare gli orari disponibili degli insegnanti.
- **Storico Lezioni**: Visualizzare lo storico delle lezioni prenotate e completate.
- **Cercare un insegnante**: O tramite mail o tramite un link invito.

## Installazione

1. Clona il repository:
   ```
   git clone [URL_DEL_REPOSITORY]
   ```

2. Importa il file `DB.sql` nel tuo database MySQL.

3. Configura le credenziali del database nel file `connessione.php`.

4. Assicurati che il server web abbia i permessi di scrittura necessari per le directory di upload e cache.

## Tecnologie Utilizzate

- PHP 7.4+
- MySQL 5.7+
- HTML5, CSS3, JavaScript
- Design responsive per dispositivi mobili
- AJAX per le interazioni dinamiche

## Struttura del Progetto

- `/login/` - Contiene i file relativi all'autenticazione
- `/front-end/` - Contiene le pagine del pannello di controllo
- `/api/` - Contiene gli script PHP per le operazioni CRUD
- `/styles/` - Contiene i file CSS per lo stile
- `/error/` - Contiene le pagine di errore personalizzate
- `/utils/` - Contiene funzioni di utilità

## Contribuire

Se desideri contribuire al progetto, puoi farlo nei seguenti modi:

1. Segnalazione di bug o richieste di funzionalità tramite issue
2. Invio di pull request con miglioramenti o correzioni
3. Miglioramento della documentazione

## Licenza

Questo progetto è rilasciato sotto la licenza [inserire tipo di licenza]. Vedere il file LICENSE per maggiori dettagli.