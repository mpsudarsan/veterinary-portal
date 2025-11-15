<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery | Department of Animal Husbandry & Veterinary Services | A&N Administration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --govt-blue: #0066b3;
            --govt-gold: #ffcc00;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 16px;
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
        
        .govt-seal {
            max-height: 80px;
        }
        
        .footer {
            background-color: var(--govt-blue);
            color: white;
            padding: 30px 0 10px;
            margin-top: 30px;
        }
        
        /* Accessibility Bar Styles */
        .accessibility-bar {
            background-color: #f8f9fa;
            padding: 5px 0;
            border-bottom: 1px solid #dee2e6;
            font-size: 14px;
        }
        .accessibility-items {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 15px;
        }
        .accessibility-link {
            color: #495057;
            text-decoration: none;
            cursor: pointer;
        }
        .accessibility-link:hover {
            color: #0d6efd;
        }
        .divider {
            width: 1px;
            height: 20px;
            background-color: #dee2e6;
        }
        
        /* Gallery Styles */
        .gallery-header {
            background-color: var(--govt-blue);
            color: white;
            padding: 15px;
            margin-bottom: 30px;
            border-radius: 5px;
        }
        
        .gallery-category {
            margin-bottom: 30px;
        }
        
        .gallery-category h3 {
            color: var(--govt-blue);
            border-bottom: 2px solid var(--govt-gold);
            padding-bottom: 8px;
            margin-bottom: 20px;
        }
        
        .gallery-item {
            margin-bottom: 20px;
            transition: transform 0.3s;
        }
        
        .gallery-item:hover {
            transform: translateY(-5px);
        }
        
        .gallery-card {
            border: 1px solid #dee2e6;
            border-radius: 5px;
            overflow: hidden;
            height: 100%;
        }
        
        .gallery-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        
        .gallery-card-body {
            padding: 15px;
            background-color: #f8f9fa;
        }
        
        .gallery-card-title {
            font-weight: bold;
            color: var(--govt-blue);
            margin-bottom: 5px;
        }
        
        .gallery-card-date {
            font-size: 0.8rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <!-- Accessibility Bar -->
    <div class="accessibility-bar">
        <div class="container">
            <div class="accessibility-items">
                <a href="index.php" class="accessibility-link">SKIP TO MAIN CONTENT</a>
                <div class="divider"></div>
                <a href="#" class="accessibility-link" onclick="increaseFontSize()">A+</a>
                <a href="#" class="accessibility-link" onclick="normalFontSize()">A</a>
                <a href="#" class="accessibility-link" onclick="decreaseFontSize()">A-</a>
                <div class="divider"></div>
                <a href="#" class="accessibility-link">ENGLISH</a>
            </div>
        </div>
    </div>
    
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
                    <a href="#" class="text-white"><i class="bi bi-envelope"></i> dir-ah[at]and[dot]nic[dot]in</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Logo and Government Identity Section -->
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

    <!-- Main Navigation -->
    <nav class="navbar navbar-expand-lg nav-main">
        <div class="container">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php"><i class="bi bi-house-door"></i> Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="about.php"><i class="bi bi-info-circle"></i> About Us</a></li>
                    <li class="nav-item"><a class="nav-link" href="locate.php"><i class="bi bi-geo"></i> Locate Us</a></li>
                    <li class="nav-item"><a class="nav-link" href="Vaccination.php"><i class="bi bi-syringe"></i> Vaccination</a></li>
                    <li class="nav-item"><a class="nav-link active" href="gallery.php"><i class="bi bi-images"></i> Gallery</a></li>
                    <li class="nav-item"><a class="nav-link" href="contact.php"><i class="bi bi-telephone"></i> Contact</a></li>
                </ul>
                <div class="d-flex">
                    <?php if (isset($_SESSION['user'])): ?>
                        <a href="logout.php" class="btn btn-warning btn-sm"><i class="bi bi-box-arrow-right"></i> Logout</a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-warning btn-sm"><i class="bi bi-lock"></i> Login</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-4" id="main-content">
        <div class="row">
            <!-- Left Sidebar -->
            <div class="col-md-3">
                <?php if (isset($_SESSION['user'])): ?>
                    <div class="user-profile mb-4">
                        <div class="text-center mb-3">
                            <i class="bi bi-person-circle" style="font-size: 3rem; color: var(--govt-blue);"></i>
                        </div>
                        <h5 class="text-center"><?= htmlspecialchars($_SESSION['user']['name'] ?? 'User') ?></h5>
                        <p class="text-center text-muted">Pet Owner</p>
                        <hr>
                        <p><i class="bi bi-person-badge"></i> <strong>Pet Owner ID:</strong> <?= htmlspecialchars($_SESSION['user']['pet_owner_id'] ?? 'N/A') ?></p>
                        <p><i class="bi bi-telephone"></i> <strong>Mobile:</strong> <?= htmlspecialchars($_SESSION['user']['mobile'] ?? 'N/A') ?></p>
                    </div>
                <?php endif; ?>
                
                <!-- Quick Links -->
                <div class="card mb-4">
                    <div class="card-header" style="background-color: var(--govt-blue); color: white;">
                        <i class="bi bi-link-45deg"></i> Quick Links
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            <li><a href="pet_registration.php" class="text-decoration-none"><i class="bi bi-chevron-right"></i> Pet Registration</a></li>
                            <li><a href="Vaccination.php" class="text-decoration-none"><i class="bi bi-chevron-right"></i> Vaccination Schedule</a></li>
                            <li><a href="locate.php" class="text-decoration-none"><i class="bi bi-chevron-right"></i> Find Veterinary Center</a></li>
                            <li><a href="#" class="text-decoration-none"><i class="bi bi-chevron-right"></i> Download Forms</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Main Content Area -->
            <div class="col-md-9">
                <div class="gallery-header">
                    <h3><i class="bi bi-images"></i> Photo Gallery</h3>
                    <p class="mb-0">Explore our collection of images showcasing departmental activities and events</p>
                </div>
                
                <!-- Events Gallery -->
                <div class="gallery-category">
                    <h3><i class="bi bi-calendar-event"></i> Departmental Events</h3>
                    <div class="row">
                        <div class="col-md-4 gallery-item">
                            <div class="gallery-card">
                                <img src="https://scontent.fixz2-1.fna.fbcdn.net/v/t39.30808-6/503711861_1155704783237652_7843773988148997901_n.jpg?_nc_cat=103&ccb=1-7&_nc_sid=127cfc&_nc_ohc=fTQ8frwv-IwQ7kNvwEN8jsO&_nc_oc=AdnK9qC-kSknqqf8OWbkDEQeWrFaRDDp8nXb8sjP94D-YbC-HX5-naXEq6-YLVMptjuqBMDl22pyvEkpdxUy32VP&_nc_zt=23&_nc_ht=scontent.fixz2-1.fna&_nc_gid=9yX7iOEpqAVblPrOwRuwHw&oh=00_AfMoy0hGvUsugOETEnMdaqVIb0Uj-zH-rlaI-YLP3X093g&oe=6851904D" alt="Vaccination Camp">
                                <div class="gallery-card-body">
                                    <div class="gallery-card-title">FMD Vaccination Programme</div>
                                    <div class="gallery-card-date">03 June 2025</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 gallery-item">
                            <div class="gallery-card">
                                <img src="https://dt.andaman.gov.in/newsimages/250307026.jpg" alt="Awareness Program">
                                <div class="gallery-card-body">
                                    <div class="gallery-card-title">Animal Birth Control Awareness</div>
                                    <div class="gallery-card-date">14 Feb 2025- 13 March 2025</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 gallery-item">
                            <div class="gallery-card">
                                <img src="https://ahvs.andaman.gov.in/images/gallery/event3.jpg" alt="Training Session">
                                <div class="gallery-card-body">
                                    <div class="gallery-card-title">Veterinary Training Program</div>
                                    <div class="gallery-card-date">28 September 2024</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Facilities Gallery -->
                <div class="gallery-category">
                    <h3><i class="bi bi-building"></i> Our Facilities</h3>
                    <div class="row">
                        <div class="col-md-4 gallery-item">
                            <div class="gallery-card">
                                <img src="https://lh3.googleusercontent.com/p/AF1QipPUQwZSN520iSHeL3DnWWvmIsNNNZZsOgtNqnoe=w600-k" alt="Veterinary Hospital">
                                <div class="gallery-card-body">
                                    <div class="gallery-card-title">Port Blair Veterinary Hospital</div>
                                    <div class="gallery-card-date">Main facility</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 gallery-item">
                            <div class="gallery-card">
                                <img src="https://ahvs.andaman.gov.in/images/gallery/facility2.jpg" alt="Diagnostic Lab">
                                <div class="gallery-card-body">
                                    <div class="gallery-card-title">Diagnostic Laboratory</div>
                                    <div class="gallery-card-date">State-of-the-art equipment</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 gallery-item">
                            <div class="gallery-card">
                                <img src="https://pbs.twimg.com/media/Gh4_onhbkAAvq4g?format=jpg&name=large" alt="Animal Shelter">
                                <div class="gallery-card-body">
                                    <div class="gallery-card-title">Animal Shelter</div>
                                    <div class="gallery-card-date">Animal care</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Team Gallery -->
                <div class="gallery-category">
                    <h3><i class="bi bi-people"></i> Our Team</h3>
                    <div class="row">
                        <div class="col-md-4 gallery-item">
                            <div class="gallery-card">
                                <img src="https://ahvs.andaman.gov.in/images/gallery/team1.jpg" alt="Veterinary Team">
                                <div class="gallery-card-body">
                                    <div class="gallery-card-title">Veterinary Doctors</div>
                                    <div class="gallery-card-date">Dedicated professionals</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 gallery-item">
                            <div class="gallery-card">
                                <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSU5OqctuOD7ar8_0Od9lFG4HsGoO33LTlnKw&s" alt="Support Staff">
                                <div class="gallery-card-body">
                                    <div class="gallery-card-title">Support Staff</div>
                                    <div class="gallery-card-date">Always ready to help</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 gallery-item">
                            <div class="gallery-card">
                                <img src="https://ahvs.andaman.gov.in/admin-pannel/photos/d16.png" alt="Pet Show">
                                <div class="gallery-card-body">
                                    <div class="gallery-card-title">Pet Show at Sri Vijaya Puram</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
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
                        Department of Animal Husbandry and Veterinary Services<br>
                        Haddo, Port Blair Andaman and Nicobar Islands<br>
                        <i class="bi bi-telephone"></i> 03192-233286(O)<br>
                        <i class="bi bi-envelope"></i> dir-ah[at]and[dot]nic[dot]in
                    </address>
                </div>
                
                <div class="col-md-4">
                    <h5>Important Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-white">Home</a></li>
                        <li><a href="about.php" class="text-white">About Us</a></li>
                        <li><a href="locate.php" class="text-white">Locate Centers</a></li>
                        <li><a href="Vaccination Schedule.php" class="text-white">Vaccination Schedule</a></li>
                    </ul>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12 text-center">
                    <p class="mb-0"><strong>© 2025 Department of Animal Husbandry & Veterinary Services, A&N Administration. All Rights Reserved.</strong></p>
                    <p class="mb-0"><small>Designed & Developed by: Team NIC</small></p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Font size adjustment functions
        function increaseFontSize() {
            document.body.style.fontSize = '20px';
        }
        function normalFontSize() {
            document.body.style.fontSize = '16px';
        }
        function decreaseFontSize() {
            document.body.style.fontSize = '14px';
        }
        
        // Skip to main content functionality
        document.querySelector('a[href="#main-content"]').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('main-content').setAttribute('tabindex', '-1');
            document.getElementById('main-content').focus();
        });
    </script>
</body>
</html>