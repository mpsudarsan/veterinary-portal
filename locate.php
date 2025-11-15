<?php include 'includes/header.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Locate Veterinary Centers | A&N Veterinary Services</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        #map {
            height: 500px;
            width: 100%;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
        .district-card {
            transition: all 0.3s ease;
            border-left: 4px solid var(--govt-blue);
        }
        .district-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .hospital-item {
            border-bottom: 1px solid #eee;
            padding: 15px 0;
        }
        .hospital-item:last-child {
            border-bottom: none;
        }
        .nav-pills .nav-link.active {
            background-color: var(--govt-blue);
        }
        .gm-style .gm-style-iw-c {
            border-radius: 8px !important;
            padding: 15px !important;
        }
        .gm-ui-hover-effect {
            top: 10px !important;
            right: 10px !important;
        }
        .search-box {
            position: absolute;
            top: 10px;
            left: 50px;
            z-index: 1000;
            width: 300px;
        }
    </style>
</head>
<body>
    <!-- Main Content -->
    <main class="container my-5">
        <h1 class="text-center mb-4" style="color: var(--govt-blue);">
            <i class="bi bi-geo-alt-fill"></i> LOCATE VETERINARY CENTRES
        </h1>
        
        <!-- Interactive Map -->
        <div class="card mb-5 position-relative">
            <div id="map"></div>
            <div class="search-box card shadow-sm">
                <div class="card-body p-2">
                    <div class="input-group">
                        <input type="text" id="pac-input" class="form-control" placeholder="Search location...">
                        <button class="btn btn-outline-secondary" type="button" id="current-location">
                            <i class="bi bi-geo-alt"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-white">
                <small class="text-muted">Click on markers for center details. Use search to find nearest centers.</small>
            </div>
        </div>
        
        <div class="row">
            <!-- District Navigation -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-white">
                        <h4 class="mb-0">Districts</h4>
                    </div>
                    <div class="card-body p-0">
                        <ul class="nav nav-pills flex-column" id="districtTab" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="south-tab" data-bs-toggle="pill" href="#south" role="tab">
                                    <i class="bi bi-geo"></i> South Andaman
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="north-tab" data-bs-toggle="pill" href="#north" role="tab">
                                    <i class="bi bi-geo"></i> North & Middle Andaman
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="nicobar-tab" data-bs-toggle="pill" href="#nicobar" role="tab">
                                    <i class="bi bi-geo"></i> Nicobar
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="card mt-3">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Emergency Contacts</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">
                                <i class="bi bi-telephone text-danger"></i> <strong>24/7 Helpline:</strong> 
                            </li>
                            <li class="list-group-item">
                                <i class="bi bi-phone text-danger"></i> <strong>Mobile Units:</strong> 
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- District Content -->
            <div class="col-md-8">
                <div class="tab-content" id="districtTabContent">
                    <!-- South Andaman -->
                    <div class="tab-pane fade show active" id="south" role="tabpanel">
                        <div class="card district-card">
                            <div class="card-header" style="background-color: var(--govt-blue); color: white;">
                                <h4 class="mb-0">South Andaman District</h4>
                            </div>
                            <div class="card-body">
                                <div class="hospital-item">
                                    <h5><i class="bi bi-hospital"></i>Veterinary Hospital</h5>
                                    <p><i class="bi bi-geo-fill"></i>Thomas Colony, Junglighat, Sri Vijaya Puram, Andaman and Nicobar Islands 744101</p>
                                    <p><i class="bi bi-telephone"></i></p>
                                    <p><i class="bi bi-clock"></i> 8AM - 12PM, 2PM - 4PM</p>
                                    <button class="btn btn-sm btn-outline-primary view-on-map" data-lat="11.664170303250469" data-lng="92.7328406224006">
                                        <i class="bi bi-map"></i> View on Map
                                    </button>
                                </div>
                                
                                <div class="hospital-item">
                                    <h5><i class="bi bi-hospital"></i> Garacharma Veterinary Center</h5>
                                    <p><i class="bi bi-geo-fill"></i> Bathubasthi, Garacharama, Sri Vijaya Puram, Andaman and Nicobar Islands 744105</p>
                                    <p><i class="bi bi-telephone"></i> </p>
                                    <p><i class="bi bi-clock"></i> 9AM - 4PM (Mon-Sat)</p>
                                    <button class="btn btn-sm btn-outline-primary view-on-map" data-lat="11.620273128314734" data-lng="92.71472404008243">
                                        <i class="bi bi-map"></i> View on Map
                                    </button>
                                </div>
                                <div class="hospital-item">
                                    <h5><i class="bi bi-hospital"></i> Veterinary Dispensary, Portmout</h5>
                                    <p><i class="bi bi-geo-fill"></i> Great Andaman Trunk Rd, Chouldari, Andaman and Nicobar Islands 744103</p>
                                    <p><i class="bi bi-telephone"></i> </p>
                                    <p><i class="bi bi-clock"></i> </p>
                                    <button class="btn btn-sm btn-outline-primary view-on-map" data-lat="11.644860082281085" data-lng="92.65433990653057">
                                        <i class="bi bi-map"></i> View on Map
                                    </button>
                                </div>
                                <div class="hospital-item">
                                    <h5><i class="bi bi-hospital"></i> Hathitapu Vet Hospital</h5>
                                    <p><i class="bi bi-geo-fill"></i> Mithakhari RV, Andaman and Nicobar Islands 744206</p>
                                    <p><i class="bi bi-telephone"></i> </p>
                                    <p><i class="bi bi-clock"></i> </p>
                                    <button class="btn btn-sm btn-outline-primary view-on-map" data-lat="11.665630442208265" data-lng="92.68956915982768">
                                        <i class="bi bi-map"></i> View on Map
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>   
                </div>
            </div>
        </div>
    </main>

<?php include 'includes/footer.php'; ?>
    <!-- JavaScript Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Google Maps API -->
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyC8DDihikxmrqAwpaqBjFlbraPXu4hmVwk&libraries=places&callback=initMap" async defer></script>
    
    <script>
        let map;
        let markers = [];
        let infoWindow;
        let autocomplete;

        // Initialize Map
        function initMap() {
            // Center on Andaman & Nicobar Islands
            map = new google.maps.Map(document.getElementById("map"), {
                center: { lat: 11.667026, lng: 92.735982 },//, 92.73287316669408
                zoom: 9,
                styles: [
                    {
                        "featureType": "poi.medical",
                        "elementType": "labels.icon",
                        "stylers": [{ "visibility": "on" }]
                    }
                ]
            });

            infoWindow = new google.maps.InfoWindow();
            
            // Initialize search box
            const input = document.getElementById("pac-input");
            autocomplete = new google.maps.places.Autocomplete(input);
            autocomplete.bindTo("bounds", map);
            
            // Add current location button
            document.getElementById("current-location").addEventListener("click", () => {
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(
                        (position) => {
                            const pos = {
                                lat: position.coords.latitude,
                                lng: position.coords.longitude
                            };
                            map.setCenter(pos);
                            map.setZoom(14);
                            
                            // Add marker for current location
                            new google.maps.Marker({
                                position: pos,
                                map: map,
                                title: "Your Location",
                                icon: {
                                    url: "http://maps.google.com/mapfiles/ms/icons/blue-dot.png"
                                }
                            });
                        },
                        () => {
                            alert("Error: The Geolocation service failed or your browser doesn't support it.");
                        }
                    );
                } else {
                    alert("Error: Your browser doesn't support geolocation.");
                }
            });

            // Add veterinary centers
            const centers = [
                {
                    name: "Port Blair Veterinary Hospital",
                    lat: 11.663841081688625,
                    lng: 92.73318392658489,
                    address: "MP7M+68J, Thomas Colony, Junglighat, Sri Vijaya Puram,744101",
                    phone: "",
                    hours: "8am to 12pm"
                },
                {
                    name: "Garacharma Veterinary Center",
                    lat: 11.622668430687973,
                    lng: 92.7144850200665,
                    address: "JPC7+3VP, Bathubasthi, Garacharama, Sri Vijaya Puram,744105",
                    phone: "",
                    hours: "9am to 4pm"
                },
                {
                    name: "Veterinary Dispensary, Portmout",
                    lat: 11.64298869620698, 
                    lng: 92.65487503982864,
                    address: "JMV3+4WW, Great Andaman Trunk Rd, Chouldari,744103",
                    phone: "",
                    hours: ""
                },
                {
                    name: "Hathitapu Vet Hospital",
                    lat: 11.665600617932812, 
                    lng: 92.68963406452188,
                    address: "MM8Q+6RF, Mithakhari RV, Andaman and Nicobar Islands 744206",
                    phone: "",
                    hours: ""
                },
                
            ];

            // Add markers for each center
            centers.forEach(center => {
                const marker = new google.maps.Marker({
                    position: { lat: center.lat, lng: center.lng },
                    map: map,
                    title: center.name,
                    icon: {
                        url: "http://maps.google.com/mapfiles/ms/icons/red-dot.png"
                    }
                });

                markers.push(marker);

                marker.addListener("click", () => {
                    infoWindow.setContent(`
                        <div style="padding: 10px; max-width: 250px;">
                            <h5 style="color: var(--govt-blue); margin-bottom: 5px;">${center.name}</h5>
                            <p style="margin: 5px 0;"><i class="bi bi-geo-alt"></i> ${center.address}</p>
                            <p style="margin: 5px 0;"><i class="bi bi-telephone"></i> ${center.phone}</p>
                            ${center.hours ? `<p style="margin: 5px 0;"><i class="bi bi-clock"></i> ${center.hours}</p>` : ''}
                            <button class="btn btn-sm btn-primary mt-2" onclick="getDirections(${center.lat}, ${center.lng})">
                                <i class="bi bi-signpost"></i> Get Directions
                            </button>
                        </div>
                    `);
                    infoWindow.open(map, marker);
                });
            });

            // Search box functionality
            autocomplete.addListener("place_changed", () => {
                const place = autocomplete.getPlace();
                if (!place.geometry) {
                    alert("No details available for input: '" + place.name + "'");
                    return;
                }

                map.setCenter(place.geometry.location);
                map.setZoom(14);
            });
        }

        // View on Map button handler
        document.querySelectorAll('.view-on-map').forEach(button => {
            button.addEventListener('click', function() {
                const lat = parseFloat(this.dataset.lat);
                const lng = parseFloat(this.dataset.lng);
                
                map.setCenter({ lat, lng });
                map.setZoom(16);
                
                // Find and open the corresponding marker's info window
                markers.forEach(marker => {
                    if (marker.getPosition().lat() === lat && marker.getPosition().lng() === lng) {
                        google.maps.event.trigger(marker, 'click');
                    }
                });
            });
        });

        // District tab click handler
        document.querySelectorAll('#districtTab a').forEach(tab => {
            tab.addEventListener('click', function() {
                const district = this.getAttribute('href').substring(1);
                if(district === 'south') {
                    map.setCenter({ lat: 11.6700, lng: 92.7376 });
                    map.setZoom(11);
                } else if(district === 'north') {
                    map.setCenter({ lat: 12.9246, lng: 92.9120 });
                    map.setZoom(9);
                } else if(district === 'nicobar') {
                    map.setCenter({ lat: 9.1667, lng: 92.7833 });
                    map.setZoom(9);
                }
            });
        });

        // Get Directions function
        function getDirections(lat, lng) {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        const userLat = position.coords.latitude;
                        const userLng = position.coords.longitude;
                        window.open(`https://www.google.com/maps/dir/?api=1&origin=${userLat},${userLng}&destination=${lat},${lng}&travelmode=driving`);
                    },
                    () => {
                        window.open(`https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}&travelmode=driving`);
                    }
                );
            } else {
                window.open(`https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}&travelmode=driving`);
            }
        }
    </script>
</body>
</html>