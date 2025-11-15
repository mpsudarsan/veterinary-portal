<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Department of Animal Husbandry & Veterinary Services | A&N Administration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --govt-blue: #0066b3;
            --govt-gold: #ffcc00;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .header {
            background-color: var(--govt-blue);
            color: white;
            padding: 8px 0;
            border-bottom: 4px solid var(--govt-gold);
        }
        
        .logo-section {
            background: white;
            padding: 15px 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .nav-main {
            background-color: var(--govt-blue);
        }
        
        .nav-main .nav-link {
            color: white !important;
            font-weight: 500;
            padding: 10px 20px;
        }
        
        .nav-main .nav-link:hover {
            background-color: #004f8a;
        }
        
        .slider-section {
            margin-top: 15px;
        }
        
        .govt-seal {
            max-height: 80px;
        }
        
        .footer {
            background-color: var(--govt-blue);
            color: white;
            padding: 30px 0 10px;
            margin-top: 30px;
        }
        
        .quick-links a {
            color: white;
            text-decoration: none;
            display: block;
            margin-bottom: 8px;
        }
        
        .login-box {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
            background: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .announcement {
            border-left: 4px solid var(--govt-blue);
            padding-left: 15px;
            margin-bottom: 20px;
        }
        
        .content-paragraph {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-top: 20px;
            height: 100%;
        }
        
        .main-content-wrapper {
            display: flex;
            flex-direction: row;
        }
        
        .left-content {
            width: 60%;
            padding-right: 20px;
        }
        
        .right-content {
            width: 40%;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .right-image {
            width: 100%;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .vaccination-image {
            max-width: 100%;
            height: auto;
            border: 3px solid var(--govt-blue);
            border-radius: 8px;
        }
        
        /* New styles for image section on right */
        .image-content {
            width: 40%;
        }
        
        .text-content {
            width: 60%;
            padding-right: 20px;
        }
        
        .nav-with-speech {
            display: flex;
            align-items: center;
            position: relative;
            width: 100%;
        }

        .navbar-collapse {
            margin-left: 7px; /* Give space for the speech controls */
        }
        .additional-images {
            margin-top: 20px;
        }
        #speechControls {
    background: var(--govt-blue);
    color: white;
    padding: 5px;
    display: flex;
    justify-content: flex-start;
    gap: 5px;
    margin-right: 15px;
    position: absolute;
    left: 1280px;
    top: 50%;
    transform: translateY(-50%);
    z-index: 1000;
    border-radius: 4px;
}
        
        
        #speechControls button {
            padding: 3px 8px;
            font-size: 12px;
            border-radius: 4px;
            border: none;
        }
        
        #startReading {
            background-color: #28a745;
            color: white;
        }
        
        #pauseReading {
            background-color: #ffc107;
            color: black;
        }
        
        #stopReading {
            background-color: #dc3545;
            color: white;
        }
        
        .highlight {
            background-color: yellow;
            transition: background-color 0.3s;
        }
        
        .nav-with-speech {
            display: flex;
            align-items: center;
        }
    </style>
</head>
<body>
    <!-- Top Header Strip -->
    <div class="header">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <marquee behavior="scroll" direction="left" scrollamount="3">
                        Department of Animal Husbandry & Veterinary Services | Andaman & Nicobar Administration
                    </marquee>
                </div>
                <div class="col-md-6 text-end">
                    <a href="#" class="text-white me-3"><i class="bi bi-telephone"></i> Helpline: 03192-238881</a>
                    <a href="#" class="text-white"><i class="bi bi-envelope"></i> dahvs[dot]and[at]nic[dot]in</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="logo-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-2 text-center">
                    <img src="https://ahvs.andaman.gov.in/img/logo.png" alt="Government Emblem" class="govt-seal">
                </div>
                <div class="col-md-8 text-center">
                    <h3 style="color: var(--govt-blue); margin-bottom: 0;">डेयरी एवं पशुपालन विभाग</h3>
                    <h3 style="color: var(--govt-blue); margin-bottom: 0;">Department of Animal Husbandry & Veterinary Services</h3>
                    <h4 style="color: var(--govt-blue);">Andaman & Nicobar Administration</h4>
                </div>
                <div class="col-md-2 text-center">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/8/8b/Seal_of_Andaman_and_Nicobar_Islands.svg" alt="Andaman Logo" class="govt-seal">
                </div>
            </div>
        </div>
    </div>

    <nav class="navbar navbar-expand-lg nav-main">
        <div class="container">
            <div class="nav-with-speech">
                <!-- Screen Reader Controls -->
                <div id="speechControls">
                    <button id="startReading" class="btn" title="Start reading"><i class="bi bi-play-fill"></i></button>
                    <button id="pauseReading" class="btn" title="Pause reading"><i class="bi bi-pause-fill"></i></button>
                    <button id="stopReading" class="btn" title="Stop reading"><i class="bi bi-stop-fill"></i></button>
                </div>
                
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="mainNav">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item"><a class="nav-link" href="index.php"><i class="bi bi-house-door"></i> Home</a></li>
                        <li class="nav-item"><a class="nav-link" href="about.php"><i class="bi bi-info-circle"></i> About Us</a></li>
                        <li class="nav-item"><a class="nav-link" href="locate.php"><i class="bi bi-geo"></i> Locate Us</a></li>
                        <li class="nav-item"><a class="nav-link" href="Vaccination Schedule.php"><i class="bi bi-file-earmark-text"></i>Vaccination Schedule</a></li>
                        <li class="nav-item"><a class="nav-link" href="gallery.php"><i class="bi bi-images"></i> Gallery</a></li>
                        <li class="nav-item"><a class="nav-link" href="contact.php"><i class="bi bi-telephone"></i> Contact</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-4">
        <div class="main-content-wrapper">
            <!-- Left Column - Text Content -->
            <div class="text-content">
                <div class="content-paragraph">
                    <h3>Animal Welfare</h3>
                    <p>Contents on this website is published and managed by the Directorate of Animal Husbandry & Veterinary Services
                    Vaccination plays a vital role in promoting animal welfare by ensuring that animals remain healthy, active, and free from
                     preventable diseases. When animals are vaccinated on time, they develop immunity against harmful infections that could otherwise cause severe discomfort,
                      long-term illness, or premature death. Healthy animals are better able to grow, reproduce, and live fulfilling lives, whether as cherished pets or
                       productive livestock. Regular vaccination not only prevents suffering but also reduces the need for costly medical treatments, surgeries, or prolonged
                        medication in the future. By protecting animals from dangerous diseases, vaccination directly contributes to their overall well-being and helps them enjoy longer, happier lives in the care of responsible owners.
                    </p>
                    <p>
                         <h4>Why is Vaccination Important in Animals?</h4>
Vaccination plays a crucial role in protecting the health and well-being of animals. Just like in humans, vaccines help animals build immunity against various infectious diseases that can otherwise lead to serious illness or even death. Here's why animal vaccination is essential:
                    </p>
                    <p>
                        <h4>Prevents Serious Diseases</h4>
Vaccines safeguard animals from life-threatening diseases such as rabies, distemper, parvovirus in dogs, foot-and-mouth disease in cattle, and other contagious conditions. These diseases, if left unchecked, can spread rapidly within animal populations and sometimes to humans (known as zoonotic diseases).
                    </p>
                    <p>
                        <h4>Controls the Spread of Contagious Infections</h4>
Vaccinated animals are less likely to carry and transmit infectious diseases to other animals. In livestock farms, pet shelters, or wildlife reserves, vaccination helps in controlling outbreaks and maintaining overall herd health.
                    </p>
                    <p>
                        <h4>Promotes Animal Welfare and Longevity</h4>
Vaccination improves the quality of life for animals by protecting them from painful and debilitating illnesses. Healthy animals live longer, lead more active lives, and require fewer medical treatments for preventable diseases.
                    </p>
                    <p>
                        <h4>Protects Public Health (Zoonosis Control)</h4>
Certain animal diseases like rabies can be transmitted to humans. Vaccinating domestic pets, livestock, and stray animals significantly reduces the risk of zoonotic disease outbreaks, protecting both animal owners and the community.
                    </p>
                    <p>
                        <h4>Economic Benefits for Livestock Farmers</h4>
For livestock owners, vaccination programs prevent disease outbreaks that could lead to mass mortality, reduced productivity, and financial losses. Healthy animals yield better milk, meat, eggs, and wool, contributing to the farmer's livelihood and food security.
                    </p>
                    <p>
                        <h4>Compliance with Legal and Travel Requirements</h4>
Many countries and regions require proof of vaccinations for pet ownership, travel, breeding, or participation in animal shows and competitions. Regular vaccinations ensure animals meet legal health standards and avoid penalties or restrictions.
                    </p>
                    <p>
Vaccination is a simple, cost-effective, and highly impactful preventive measure that not only protects individual animals but also safeguards entire animal populations and public health. Every responsible pet owner, livestock keeper, and veterinary professional should prioritize timely vaccinations to ensure a healthier, safer world for both animals and humans.
                    </p>
                </div>
            </div>
            
            <!-- Right Column - Images -->
            <div class="image-content">
                <!-- Image Slider -->
                <div class="slider-section">
                    <div id="mainSlider" class="carousel slide" data-bs-ride="carousel">
                        <div class="carousel-inner">
                            <!-- Slide 1 -->
                            <div class="carousel-item active">
                                <img src="https://www.drjasmeetvetcare.com/images/known-for/multi-specialty-veterinary-hospital.webp" 
                                     class="d-block w-100" 
                                     alt="Veterinary Hospital Services">
                            </div>
                            
                            <!-- Slide 2 -->
                            <div class="carousel-item">
                                <img src="https://aniportalimages.s3.amazonaws.com/media/details/WhatsApp_Image_2021-10-30_at_1.31.47_PM.jpeg" 
                                     class="d-block " 
                                     alt="Animal Care Examination">
                            </div>
                            
                            <!-- Slide 3 - Cattle Vaccination -->
                            <div class="carousel-item">
                                <img src="https://lbah.com/wp-content/uploads/2015/11/Duck-gas-anesthesia.jpg" 
                                     class="d-block w-100" 
                                     alt="Cattle Vaccination Program">
                            </div>
                            
                            <!-- Slide 4 - Poultry Farm -->
                            <div class="carousel-item">
                                <img src="https://media.ouest-france.fr/v1/pictures/MjAyMTA5NmM2MTEyY2EwYTEwMThiNWNhMTgzZjY1OGI4NTFiYTU?width=1260&height=708&focuspoint=50%2C24&cropresize=1&client_id=bpeditorial&sign=3d5e28256d59b3b6117bc67369d0140ff6cfceb2d4a1056a9ee73a0a077b9279" 
                                     class="d-block w-100" 
                                     alt="Modern Poultry Farm">
                            </div>
                            
                            <!-- Slide 5 - Veterinary Camp -->
                            <div class="carousel-item">
                                <img src="https://archive-images.prod.global.a201836.reutersmedia.net/2018/11/18/LYNXNPEEAH0BA.JPG" 
                                     class="d-block w-100" 
                                     alt="Rural Veterinary Camp">
                            </div>
                              
                            <!-- Slide 6 - Dairy Farming -->
                            <div class="carousel-item">
                                <img src="https://i.ytimg.com/vi/C8gIt-WT_88/maxresdefault.jpg" 
                                    class="d-block w-100"      
                                     alt="Dairy Cattle Farming">
                            </div>
                        </div>
                        
                        <!-- Carousel Controls -->
                        <button class="carousel-control-prev" type="button" data-bs-target="#mainSlider" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon"></span>
                            <span class="visually-hidden">Previous</span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#mainSlider" data-bs-slide="next">
                            <span class="carousel-control-next-icon"></span>
                            <span class="visually-hidden">Next</span>
                        </button>
                        
                        <!-- Carousel Indicators -->
                        <div class="carousel-indicators">
                            <button type="button" data-bs-target="#mainSlider" data-bs-slide-to="0" class="active"></button>
                            <button type="button" data-bs-target="#mainSlider" data-bs-slide-to="1"></button>
                            <button type="button" data-bs-target="#mainSlider" data-bs-slide-to="2"></button>
                            <button type="button" data-bs-target="#mainSlider" data-bs-slide-to="3"></button>
                            <button type="button" data-bs-target="#mainSlider" data-bs-slide-to="4"></button>
                            <button type="button" data-bs-target="#mainSlider" data-bs-slide-to="5"></button>
                        </div>
                    </div>
                </div>
                
                <!-- Additional Images -->
                <div class="additional-images">
                    <div class="right-image">
                        <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSm4CYbrEJlsM7j15YIG-GCOB27OuO1sc4BXQ&s" alt="Animal Vaccination" class="vaccination-image">
                        <p class="text-center mt-2"><strong>Vaccination Camp in Progress</strong></p>
                    </div>
                    
                    <div class="right-image">
                        <img src="https://lodivet.com/wp-content/uploads/2018/09/Lodi-Vet-Livestock-Veterinary-Hospital-Services.jpg" alt="Healthy Livestock" class="vaccination-image">
                        <p class="text-center mt-2"><strong>Healthy Vaccinated Livestock</strong></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
           
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>About Department</h5>
                    <p>The Department of Animal Husbandry & Veterinary Services provides comprehensive animal healthcare services across Andaman & Nicobar Islands.</p>
                </div>
                <div class="col-md-4">
                    <h5>Contact Us</h5>
                    <address>
                        Department of Animal Husbandry and Veterinary Services
                        Haddo, Port Blair Andaman and Nicobar Islands<br>
                        <i class="bi bi-telephone"></i> 03192-233286(O)<br>
                        <i class="bi bi-envelope"></i> dir-ah[at]and[dot]nic[dot]in
                    </address>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12 text-center">
                    <p class="mb-0">© 2025 Department of Animal Husbandry & Veterinary Services, A&N Administration. All Rights Reserved.</p>
                    <p class="mb-0">Designed & Developed by: Team NIC</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const content = document.querySelector('.content-paragraph');
            const startBtn = document.getElementById('startReading');
            const pauseBtn = document.getElementById('pauseReading');
            const stopBtn = document.getElementById('stopReading');
            
            const speechSynthesis = window.speechSynthesis;
            let currentUtterance = null;
            let paragraphs = Array.from(content.querySelectorAll('p, h3, h4'));
            let currentParagraph = 0;
            let isPaused = false;
            
            // Function to speak the current paragraph
            function speakCurrentParagraph() {
                if (currentParagraph >= paragraphs.length) {
                    resetReading();
                    return;
                }
                
                // Remove previous highlights
                document.querySelectorAll('.highlight').forEach(el => {
                    el.classList.remove('highlight');
                });
                
                // Highlight current paragraph
                const currentElement = paragraphs[currentParagraph];
                currentElement.classList.add('highlight');
                
                // Create utterance
                const text = currentElement.textContent;
                currentUtterance = new SpeechSynthesisUtterance(text);
                
                // Set English voice (filter only English voices)
                const voices = speechSynthesis.getVoices();
                const englishVoice = voices.find(voice => voice.lang.includes('en'));
                if (englishVoice) {
                    currentUtterance.voice = englishVoice;
                }
                
                // When this utterance finishes, move to next paragraph
                currentUtterance.onend = () => {
                    currentElement.classList.remove('highlight');
                    if (!isPaused) {
                        currentParagraph++;
                        speakCurrentParagraph();
                    }
                };
                
                speechSynthesis.speak(currentUtterance);
            }
            
            // Reset reading state
            function resetReading() {
                currentParagraph = 0;
                isPaused = false;
                document.querySelectorAll('.highlight').forEach(el => {
                    el.classList.remove('highlight');
                });
            }
            
            // Start reading
            startBtn.addEventListener('click', () => {
                if (speechSynthesis.speaking && !isPaused) {
                    speechSynthesis.cancel();
                }
                
                isPaused = false;
                
                // If we're at the end, start from beginning
                if (currentParagraph >= paragraphs.length) {
                    currentParagraph = 0;
                }
                
                speakCurrentParagraph();
            });
            
            // Pause reading
            pauseBtn.addEventListener('click', () => {
                if (speechSynthesis.speaking) {
                    speechSynthesis.pause();
                    isPaused = true;
                }
            });
            
            // Stop reading
            stopBtn.addEventListener('click', () => {
                speechSynthesis.cancel();
                resetReading();
            });
            
            // Load voices when they become available
            speechSynthesis.onvoiceschanged = function() {
                // Voices are loaded, no need to do anything special
            };
        });
    </script>
</body>
</html>