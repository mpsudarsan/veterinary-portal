<?php include 'includes/header.php'; ?>
<style>        
        .quick-links a {
            color: white;
            text-decoration: none;
            display: block;
            margin-bottom: 8px;
        }
        
        .vaccine-table {
            margin: 30px 0;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .vaccine-table h3 {
            background-color: black;
            color: white;
            padding: 10px;
            margin-bottom: 0;
        }
        
        .table th {
            background-color: black;
            color: white;
        }
        
        .table-striped>tbody>tr:nth-of-type(odd)>* {
            background-color: rgba(0, 0, 0, 0.05);
        }
        
        .table-bordered>:not(caption)>* {
            border-color: #dee2e6;
        }
        
        .note-box {
            background-color: #f8f9fa;
            border-left: 4px solid black;
            padding: 10px;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    
    <!-- Main Content -->
    <div class="container mt-4">
        <div class="row">
            <!-- Full Width Column -->
            <div class="col-12">
                <!-- Vaccination Schedule -->
                <div class="vaccine-table">
                    <h3 class="text-center">POULTRY VACCINATION SCHEDULE - LAYERS</h3>
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Age</th>
                                <th>Name of Vaccine</th>
                                <th>Dose</th>
                                <th>Route</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>5-7th day</td>
                                <td>Lasota</td>
                                <td>-</td>
                                <td>I/R or I/O</td>
                            </tr>
                            <tr>
                                <td>14-16th day</td>
                                <td>I.B.D.</td>
                                <td>-</td>
                                <td>I/O or D/W</td>
                            </tr>
                            <tr>
                                <td>24-26th day</td>
                                <td>I.B.D. (booster)</td>
                                <td>-</td>
                                <td>D/W</td>
                            </tr>
                            <tr>
                                <td>30th day</td>
                                <td>Lasota (booster)</td>
                                <td>-</td>
                                <td>D/W</td>
                            </tr>
                            <tr>
                                <td>7th week</td>
                                <td>Fowl Pox</td>
                                <td>0.2 ml.</td>
                                <td>I/M</td>
                            </tr>
                            <tr>
                                <td>9th week</td>
                                <td>Deworming</td>
                                <td>-</td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td>10th week</td>
                                <td>R2B</td>
                                <td>0.5 ml.</td>
                                <td>I/M</td>
                            </tr>
                            <tr>
                                <td>15th week</td>
                                <td>Debeaking</td>
                                <td>-</td>
                                <td>D/W</td>
                            </tr>
                            <tr>
                                <td>17th week</td>
                                <td>Lasota</td>
                                <td>-</td>
                                <td>-</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="vaccine-table">
                    <h3 class="text-center">POULTRY VACCINATION SCHEDULE - BROILERS</h3>
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Age</th>
                                <th>Name of Vaccine</th>
                                <th>Dose</th>
                                <th>Route</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>3-5th day</td>
                                <td>Lasota</td>
                                <td>-</td>
                                <td>I/O or I/N</td>
                            </tr>
                            <tr>
                                <td>7-9th day</td>
                                <td>I.B.D.</td>
                                <td>-</td>
                                <td>I/O or D/W</td>
                            </tr>
                            <tr>
                                <td>16-18th day</td>
                                <td>I.B.D. (booster)</td>
                                <td>-</td>
                                <td>D/W</td>
                            </tr>
                            <tr>
                                <td>24-26th day</td>
                                <td>Lasota (booster)</td>
                                <td>-</td>
                                <td>D/W</td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="note-box">
                        <strong>Note:</strong> I/N – Intra Nasal; I/O – Intra Occular; D/W – Drinking water; I/M – Intra Muscular
                    </div>
                </div>
                
                <div class="vaccine-table">
                    <h3 class="text-center">CATTLE VACCINATION SCHEDULE</h3>
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Sl. No.</th>
                                <th>Name of Vaccine</th>
                                <th>Species</th>
                                <th>Age</th>
                                <th>Dose</th>
                                <th>Route</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1.</td>
                                <td>Foot & Mouth Disease Vaccine</td>
                                <td>Cattle</td>
                                <td>
                                    Primary vaccination at 4 months of age<br>
                                    First vaccination 9 months after primary vaccination<br>
                                    Re-vaccinate annually
                                </td>
                                <td>2 ml.</td>
                                <td>I/M</td>
                            </tr>
                            <tr>
                                <td></td>
                                <td>Foot & Mouth Disease Vaccine</td>
                                <td>Pigs, Sheep & Goat</td>
                                <td>
                                    Primary vaccination at 4 months of age<br>
                                    First vaccination 9 months after primary vaccination<br>
                                    Re-vaccinate annually
                                </td>
                                <td>1 vial.</td>
                                <td>I/M</td>
                            </tr>
                            <tr>
                                <td>2.</td>
                                <td>HS/BQ Combined Vaccine</td>
                                <td>Cattle</td>
                                <td>
                                    Primary vaccination at 6 months of age or above<br>
                                    Revaccination annually
                                </td>
                                <td>4 ml.</td>
                                <td>S/C</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="vaccine-table">
                    <h3 class="text-center">PIGS VACCINATION SCHEDULE</h3>
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Sl. No.</th>
                                <th>Name of Vaccine</th>
                                <th>Species</th>
                                <th>Age</th>
                                <th>Dose</th>
                                <th>Route</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1.</td>
                                <td>Swine Fever vaccine</td>
                                <td>Pigs</td>
                                <td>
                                    Fattening pigs - a single dose at the age of 1-2 months<br>
                                    Breeding pigs - 1st vaccination at the age of 1-2 months.<br>
                                    2nd vaccination at 6 months after 1st vaccination.<br>
                                    Revaccinate once a year.
                                </td>
                                <td>1 ml</td>
                                <td>I/m or S/C</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="vaccine-table">
                    <h3 class="text-center">DOGS VACCINATION SCHEDULE</h3>
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Sl. No.</th>
                                <th>Name of Vaccine</th>
                                <th>Species</th>
                                <th>Age</th>
                                <th>Dose</th>
                                <th>Route</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1.</td>
                                <td>Anti-Rabies vaccine</td>
                                <td>Dogs & other Domestic Animals</td>
                                <td>
                                    Prophylactic use at 3 months of age.<br>
                                    Annual vaccination is recommended.
                                </td>
                                <td>1 ml.</td>
                                <td>I/m or S/C</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>


<?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>