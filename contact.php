<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Directory | Department of Animal Husbandry & Veterinary Services | A&N Administration</title>
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
        
        .directory-header {
            background-color: var(--govt-blue);
            color: white;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .directory-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        
        .directory-table th {
            background-color: var(--govt-blue);
            color: white;
            padding: 12px 15px;
            text-align: left;
        }
        
        .directory-table td {
            padding: 10px 15px;
            border-bottom: 1px solid #ddd;
        }
        
        .directory-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        .directory-table tr:hover {
            background-color: #e9ecef;
        }
        
        .section-title {
            color: var(--govt-blue);
            border-bottom: 2px solid var(--govt-blue);
            padding-bottom: 5px;
            margin-top: 30px;
            margin-bottom: 20px;
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
    </style>
</head>
<body>
    <!-- Accessibility Bar -->
    <div class="accessibility-bar">
        <div class="container">
            <div class="accessibility-items">
                <a href="#main-content" class="accessibility-link">SKIP TO MAIN CONTENT</a>
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
                    <li class="nav-item"><a class="nav-link" href="gallery.php"><i class="bi bi-images"></i> Gallery</a></li>
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
        <div class="directory-header">
            <h4><i class="bi bi-telephone-outbound"></i> Telephone Directory</h4>
        </div>
        
        <h4 class="section-title"><i class="bi bi-building"></i> Directorate Office</h4>
        <div class="table-responsive">
            <table class="directory-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Designation</th>
                        <th>Phone (Office)</th>
                        <th>Phone (Residence)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Dr. K.A. Naveen</td>
                        <td>Director</td>
                        <td>03192-233286</td>
                        <td>03192-233287</td>
                    </tr>
                    <tr>
                        <td>Dr. P.K. Mandal</td>
                        <td>Joint Director</td>
                        <td>03192-233288</td>
                        <td>03192-233289</td>
                    </tr>
                    <tr>
                        <td>Dr. S.K. Biswas</td>
                        <td>Deputy Director (Admin)</td>
                        <td>03192-233290</td>
                        <td>03192-233291</td>
                    </tr>
                    <tr>
                        <td>Dr. Anjali Kumari</td>
                        <td>Deputy Director (Veterinary)</td>
                        <td>03192-233292</td>
                        <td>03192-233293</td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <h4 class="section-title"><i class="bi bi-geo-alt"></i> District Offices</h4>
        <div class="table-responsive">
            <table class="directory-table">
                <thead>
                    <tr>
                        <th>Office</th>
                        <th>In-Charge</th>
                        <th>Phone (Office)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>South Andaman District</td>
                        <td>Dr. M. Selvam</td>
                        <td>03192-238881</td>
                    </tr>
                    <tr>
                        <td>North & Middle Andaman District</td>
                        <td>Dr. S. Venkatesan</td>
                        <td>03192-273344</td>
                    </tr>
                    <tr>
                        <td>Nicobar District</td>
                        <td>Dr. R.K. Singh</td>
                        <td>03193-265222</td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <h4 class="section-title"><i class="bi bi-hospital"></i> Veterinary Hospitals</h4>
        <div class="table-responsive">
            <table class="directory-table">
                <thead>
                    <tr>
                        <th>Hospital</th>
                        <th>In-Charge</th>
                        <th>Phone</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Port Blair Veterinary Hospital</td>
                        <td>Dr. A. Kumar</td>
                        <td>03192-232112</td>
                    </tr>
                    <tr>
                        <td>Garacharma Veterinary Hospital</td>
                        <td>Dr. B. Rao</td>
                        <td>03192-257890</td>
                    </tr>
                    <tr>
                        <td>Mayabunder Veterinary Hospital</td>
                        <td>Dr. C. Nair</td>
                        <td>03192-273456</td>
                    </tr>
                    <tr>
                        <td>Car Nicobar Veterinary Hospital</td>
                        <td>Dr. D. Sharma</td>
                        <td>03193-265111</td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <h4 class="section-title"><i class="bi bi-exclamation-triangle"></i> Emergency Contacts</h4>
        <div class="table-responsive">
            <table class="directory-table">
                <thead>
                    <tr>
                        <th>Service</th>
                        <th>Contact Number</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>24x7 Veterinary Emergency</td>
                        <td>1077</td>
                    </tr>
                    <tr>
                        <td>Animal Rescue</td>
                        <td>03192-238880</td>
                    </tr>
                    <tr>
                        <td>Disease Reporting</td>
                        <td>03192-238882</td>
                    </tr>
                </tbody>
            </table>
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
                        Haddo, Port Blair, Andaman and Nicobar Islands<br>
                        <i class="bi bi-telephone"></i> 03192-233286(O)<br>
                        <i class="bi bi-envelope"></i> dir-ah[at]and[dot]nic[dot]in
                    </address>
                </div>
                
                <div class="col-md-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-white">Pet Registration</a></li>
                        <li><a href="#" class="text-white">Vaccination Schedule</a></li>
                        <li><a href="#" class="text-white">Download Forms</a></li>
                    </ul>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12 text-center">
                    <p class="mb-0">© 2023 Department of Animal Husbandry & Veterinary Services, A&N Administration. All Rights Reserved.</p>
                    <p class="mb-0">Designed & Developed by: Team NIC</p>
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