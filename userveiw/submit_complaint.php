<?php
// Start session safely
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
include 'database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Complaint | SafiriPay</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        :root {
            --primary: #0f5132;
            --primary-dark: #0c4128;
            --secondary: #20c997;
            --danger: #dc3545;
            --warning: #ffc107;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --light-gray: #e9ecef;
            --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        body {
            background: linear-gradient(135deg, #f5f7fb 0%, #e9ecef 100%);
            min-height: 100vh;
        }

        .complaint-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        /* Header */
        .complaint-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .complaint-header h1 {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }

        .complaint-header h1 i {
            background: var(--primary);
            color: white;
            padding: 15px;
            border-radius: 50%;
            font-size: 1.8rem;
        }

        .complaint-header p {
            color: var(--gray);
            font-size: 1.1rem;
        }

        /* Complaint Card */
        .complaint-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: var(--card-shadow);
            margin-bottom: 2rem;
            border: 1px solid rgba(15, 81, 50, 0.1);
        }

        /* Info Box */
        .info-box {
            background: #e8f4f8;
            border-left: 4px solid var(--secondary);
            padding: 1rem 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .info-box i {
            font-size: 2rem;
            color: var(--secondary);
        }

        .info-box p {
            color: var(--dark);
            font-size: 0.95rem;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 1.8rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--dark);
            font-weight: 600;
            font-size: 0.95rem;
        }

        .form-group label i {
            color: var(--primary);
            margin-right: 8px;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--light-gray);
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--secondary);
            box-shadow: 0 0 0 3px rgba(32, 201, 151, 0.1);
        }

        select.form-control {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%236c757d' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 16px;
        }

        textarea.form-control {
            resize: vertical;
            min-height: 120px;
        }

        /* Complaint Type Cards */
        .type-cards {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-top: 0.5rem;
        }

        .type-card {
            border: 2px solid var(--light-gray);
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .type-card.selected {
            border-color: var(--primary);
            background: rgba(15, 81, 50, 0.05);
        }

        .type-card i {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .type-card.car i { color: var(--primary); }
        .type-card.driver i { color: var(--warning); }

        .type-card h4 {
            font-size: 1rem;
            margin-bottom: 0.25rem;
            color: var(--dark);
        }

        .type-card p {
            font-size: 0.85rem;
            color: var(--gray);
        }

        .type-card input[type="radio"] {
            display: none;
        }

        /* Submit Button */
        .btn-submit {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(15, 81, 50, 0.3);
        }

        .btn-submit i {
            font-size: 1.2rem;
        }

        /* Guidelines */
        .guidelines {
            background: var(--light);
            border-radius: 12px;
            padding: 1.5rem;
        }

        .guidelines h3 {
            color: var(--primary);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.1rem;
        }

        .guidelines ul {
            list-style: none;
        }

        .guidelines li {
            margin-bottom: 0.8rem;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--gray);
            font-size: 0.95rem;
        }

        .guidelines li i {
            color: var(--secondary);
            font-size: 1rem;
        }

        /* Loading Overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .loading-content {
            background: white;
            padding: 2rem;
            border-radius: 20px;
            text-align: center;
        }

        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 5px solid var(--light-gray);
            border-top: 5px solid var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .complaint-header h1 {
                font-size: 2rem;
            }
            
            .type-cards {
                grid-template-columns: 1fr;
            }
            
            .complaint-card {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="complaint-container">
        <!-- Header -->
        <div class="complaint-header">
            <h1>
                <i class="fas fa-exclamation-circle"></i>
                Submit a Complaint
            </h1>
            <p>Help us improve our services by reporting any issues</p>
        </div>

        <!-- Info Box -->
        <div class="info-box">
            <i class="fas fa-info-circle"></i>
            <p>Your complaint will be reviewed within 24-48 hours. You can track its status in <a href="my_complaints.php" style="color: var(--primary);">My Complaints</a>.</p>
        </div>

        <!-- Complaint Form -->
        <div class="complaint-card">
            <form id="complaintForm" action="process_complaint.php" method="POST">
                <!-- Complaint Type Selection -->
                <div class="form-group">
                    <label><i class="fas fa-tag"></i> Complaint Type</label>
                    <div class="type-cards">
                        <label class="type-card car" onclick="selectType('car', this)">
                            <input type="radio" name="type" value="car" required>
                            <i class="fas fa-bus"></i>
                            <h4>Vehicle Complaint</h4>
                            <p>Issues with bus condition, cleanliness, mechanical problems</p>
                        </label>
                        
                        <label class="type-card driver" onclick="selectType('driver', this)">
                            <input type="radio" name="type" value="driver" required>
                            <i class="fas fa-user"></i>
                            <h4>Driver/Conductor Complaint</h4>
                            <p>Issues with behavior, driving, overcharging, etc.</p>
                        </label>
                    </div>
                </div>

                <!-- Complaint Description -->
                <div class="form-group">
                    <label for="description"><i class="fas fa-pencil-alt"></i> Complaint Details</label>
                    <textarea 
                        class="form-control" 
                        id="description" 
                        name="description" 
                        placeholder="Please provide detailed information about your complaint... (e.g., bus number, date, time, location, what happened)" 
                        required
                        minlength="10"
                        maxlength="500"
                    ></textarea>
                    <div style="text-align: right; font-size: 0.8rem; color: var(--gray); margin-top: 5px;">
                        <span id="charCount">0</span>/500 characters
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn-submit" id="submitBtn">
                    <i class="fas fa-paper-plane"></i>
                    Submit Complaint
                </button>
            </form>
        </div>

        <!-- Guidelines -->
        <div class="guidelines">
            <h3><i class="fas fa-clipboard-list"></i> Complaint Guidelines</h3>
            <ul>
                <li><i class="fas fa-check-circle"></i> Be specific and provide as much detail as possible</li>
                <li><i class="fas fa-check-circle"></i> Include the bus number, date, and time of incident</li>
                <li><i class="fas fa-check-circle"></i> Keep your complaint professional and respectful</li>
                <li><i class="fas fa-check-circle"></i> False complaints may lead to account suspension</li>
                <li><i class="fas fa-check-circle"></i> You'll receive updates on your complaint status</li>
            </ul>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-content">
            <div class="loading-spinner"></div>
            <p>Submitting your complaint...</p>
        </div>
    </div>

    <script>
        // Character counter for textarea
        const description = document.getElementById('description');
        const charCount = document.getElementById('charCount');
        
        description.addEventListener('input', function() {
            const length = this.value.length;
            charCount.textContent = length;
            
            if (length > 450) {
                charCount.style.color = '#dc3545';
            } else if (length > 400) {
                charCount.style.color = '#ffc107';
            } else {
                charCount.style.color = '#6c757d';
            }
        });

        // Complaint type selection
        function selectType(type, element) {
            // Remove selected class from all cards
            document.querySelectorAll('.type-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Add selected class to clicked card
            element.classList.add('selected');
            
            // Check the radio button
            const radio = element.querySelector('input[type="radio"]');
            radio.checked = true;
        }

        // Form submission with loading
        document.getElementById('complaintForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validate form
            const type = document.querySelector('input[name="type"]:checked');
            const description = document.getElementById('description').value.trim();
            
            if (!type) {
                alert('Please select a complaint type');
                return;
            }
            
            if (description.length < 10) {
                alert('Please provide more details about your complaint (minimum 10 characters)');
                return;
            }
            
            // Show loading
            document.getElementById('loadingOverlay').style.display = 'flex';
            
            // Submit form
            this.submit();
        });

        // Auto-select based on URL parameter
        const urlParams = new URLSearchParams(window.location.search);
        const type = urlParams.get('type');
        
        if (type === 'car' || type === 'driver') {
            const card = document.querySelector(`.type-card.${type}`);
            if (card) {
                selectType(type, card);
            }
        }

        // Prevent double submission
        document.getElementById('submitBtn').addEventListener('click', function() {
            this.disabled = true;
        });
    </script>
</body>
</html>