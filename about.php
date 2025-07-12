<?php
ob_start();
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'sesion-check/check.php';
include 'plugins/bootstrap.html';
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Despre noi - Budget Master</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        body {
            margin: 0;
            padding: 0;
            background: #f8f9fa;
            color: #2d3748;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            font-family: 'Poppins', sans-serif;
        }

        .back-button {
            position: fixed;
            top: 30px;
            left: 30px;
            z-index: 1000;
            background: #000000;
            color: white;
            border: 1px solid #000000;
            padding: 12px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .back-button:hover {
            background: #333333;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            text-decoration: none;
        }

        .content-wrapper {
            flex: 1 0 auto;
            width: 100%;
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        .container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
        }

        .about-content {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            padding: 3rem 2.5rem;
            text-align: left;
            max-width: 800px;
            margin: 0 auto;
        }

        .about-content h1 {
            color: #2d3748;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 2rem;
            text-align: center;
        }

        .intro-text {
            color: #4a5568;
            font-size: 1.2rem;
            margin-bottom: 2.5rem;
            line-height: 1.6;
            text-align: center;
            background: #f8f9fa;
            padding: 2rem;
            border-radius: 10px;
            border-left: 4px solid #42a5f5;
        }

        .section {
            margin-bottom: 2.5rem;
        }

        .section h2 {
            color: #2d3748;
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .section h3 {
            color: #4a5568;
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
        }

        .section p {
            color: #718096;
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 1rem;
        }

        .section ul {
            color: #718096;
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 1rem;
            padding-left: 1.5rem;
        }

        .section li {
            margin-bottom: 0.5rem;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .feature-card {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 10px;
            border-left: 4px solid #42a5f5;
            transition: all 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .feature-card h4 {
            color: #2d3748;
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .feature-card p {
            color: #718096;
            font-size: 0.95rem;
            line-height: 1.5;
            margin: 0;
        }

        .contact-info {
            background: #e8f4fd;
            padding: 2rem;
            border-radius: 10px;
            text-align: center;
            margin-top: 2rem;
        }

        .contact-info h3 {
            color: #2d3748;
            margin-bottom: 1rem;
        }

        .contact-info p {
            color: #4a5568;
            font-size: 1rem;
            margin: 0;
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }
            
            .about-content {
                padding: 2rem 1.5rem;
            }
            
            .about-content h1 {
                font-size: 2rem;
            }
            
            .intro-text {
                font-size: 1.1rem;
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <a href="javascript:history.back()" class="back-button">
        <i class="fas fa-arrow-left"></i>
        Înapoi la site
    </a>

    <div class="content-wrapper">
        <div class="container">
            <div class="about-content">
                <h1>Despre Budget Master</h1>
                
                <div class="intro-text">
                    <p>Budget Master este o aplicație web simplă și intuitivă pentru gestionarea finanțelor personale, 
                    creată pentru a te ajuta să îți organizezi mai bine veniturile, cheltuielile și obiectivele financiare.</p>
                </div>

                <div class="section">
                    <h2><i class="fas fa-bullseye"></i> Scopul aplicației</h2>
                    <p>Aplicația a fost dezvoltată pentru a oferi o soluție accesibilă și ușor de utilizat pentru 
                    gestionarea bugetului personal. Fie că vrei să îți urmărești veniturile lunare, să planifici 
                    cheltuielile sau să îți setezi obiective de economisire, Budget Master îți oferă instrumentele 
                    necesare într-o interfață curată și prietenoasă.</p>
                </div>

                <div class="section">
                    <h2><i class="fas fa-star"></i> Funcționalități principale</h2>
                    
                    <div class="features-grid">
                        <div class="feature-card">
                            <h4><i class="fas fa-wallet"></i> Gestionarea Veniturilor</h4>
                            <p>Înregistrează și urmărește toate veniturile tale cu sisteme de notificare pentru venituri recurente.</p>
                        </div>
                        
                        <div class="feature-card">
                            <h4><i class="fas fa-chart-line"></i> Urmărirea Cheltuielilor</h4>
                            <p>Monitorizează cheltuielile pe categorii și perioadele de timp pentru o vizibilitate completă.</p>
                        </div>
                        
                        <div class="feature-card">
                            <h4><i class="fas fa-target"></i> Obiective Financiare</h4>
                            <p>Setează obiective de economisire cu milestone-uri și urmărește progresul în timp real.</p>
                        </div>
                        
                        <div class="feature-card">
                            <h4><i class="fas fa-calendar-alt"></i> Planificare Bugetară</h4>
                            <p>Organizează-ți bugetul lunar cu instrumente de planificare și previzionare.</p>
                        </div>
                        
                        <div class="feature-card">
                            <h4><i class="fas fa-chart-pie"></i> Rapoarte și Statistici</h4>
                            <p>Vizualizează progresul prin grafice și rapoarte detaliate pentru decizii informate.</p>
                        </div>
                        
                        <div class="feature-card">
                            <h4><i class="fas fa-user-shield"></i> Securitate</h4>
                            <p>Datele tale sunt protejate prin sisteme de autentificare și criptare securizate.</p>
                        </div>
                    </div>
                </div>

                <div class="section">
                    <h2><i class="fas fa-users"></i> Pentru cine este destinată</h2>
                    <p>Budget Master este perfectă pentru:</p>
                    <ul>
                        <li>Persoane care vor să își organizeze mai bine finanțele personale</li>
                        <li>Studenți care învață să gestioneze primul buget independent</li>
                        <li>Familii care vor să planifice cheltuielile comune</li>
                        <li>Oricine dorește să economisească pentru obiective specifice</li>
                        <li>Persoane care vor să înțeleagă mai bine unde își cheltuie banii</li>
                    </ul>
                </div>

                <div class="section">
                    <h2><i class="fas fa-lightbulb"></i> Filozofia noastră</h2>
                    <p>Credem că gestionarea financiară nu trebuie să fie complicată. Budget Master oferă 
                    o abordare simplă și directă, fără funcționalități excesive care ar putea să confuze. 
                    Obiectivul nostru este să îți oferim instrumentele esențiale pentru a lua control 
                    asupra finanțelor tale într-un mod natural și intuitiv.</p>
                </div>

                <div class="section">
                    <h2><i class="fas fa-road"></i> Viitorul aplicației</h2>
                    <p>Continuăm să dezvoltăm și să îmbunătățim Budget Master pe baza feedback-ului 
                    utilizatorilor. Planurile noastre includ funcționalități noi pentru o experiență 
                    și mai bună în gestionarea finanțelor personale.</p>
                </div>

                <div class="contact-info">
                    <h3><i class="fas fa-envelope"></i> Contact</h3>
                    <p>Pentru întrebări, sugestii sau feedback, nu ezita să ne contactezi. 
                    Apreciem foarte mult opiniile utilizatorilor și le folosim pentru a îmbunătăți 
                    continuu aplicația.</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
