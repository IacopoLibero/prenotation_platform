# Programma Lezioni

Un'applicazione web per la gestione delle lezioni tra insegnanti e studenti, facilitando la prenotazione e l'organizzazione degli incontri formativi.

## roadmap di cose da fare

- quando un professore o studente deve vedere le diponbilità vengono visualizzate dinamicamente, quindi calcolate e riprese direttemente dai calendari e non dal db
    - deve esserci un bottone disponibile solo per lo studente per ogni fascia oraria disponibike che al clik crea l'evento sia per il professore che per lo studente sul calendsario e lo salvi sul database nella tabella lezioni per avere un trak delle lezioni fatte 
    - allo stesso modo quando si emimina una lezione, (o dal calendario o dalla piattaforma)sia per lo studente che per il professore deve essere eliminato dal calendario e database
- quando viene prenotata la lezione l'evento nei calendari del prof e studente deve avere questa formattazione:
    - nome "lezione con [nomeprofessore/nomestudente]"
    - notifica dal calendario 30 minuti prima 
    - link a google meet generato ma valido
    - note con "lezione prenotata con[link del sito]


