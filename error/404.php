<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagina non trovata</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Montserrat', sans-serif;
            background-color: #f5f7fa;
            color: #333;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            text-align: center;
        }
        
        .error-container {
            max-width: 600px;
            padding: 2rem;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        h1 {
            color: #2da0a8;
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
        }
        
        .btn {
            display: inline-block;
            background-color: #2da0a8;
            color: white;
            padding: 0.8rem 1.5rem;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            transition: background-color 0.3s ease;
        }
        
        .btn:hover {
            background-color: #238e95;
        }
        
        .error-code {
            font-size: 8rem;
            font-weight: bold;
            color: rgba(45, 160, 168, 0.2);
            position: absolute;
            z-index: -1;
        }
    </style>
</head>
<body>
    <div class="error-code">404</div>
    <div class="error-container">
        <h1>Pagina Non Trovata</h1>
        <p>Ci dispiace, ma la pagina che stai cercando non esiste o è stata spostata.</p>
        <a href="../index.php" class="btn">Torna alla Home</a>
    </div>
</body>
</html>
