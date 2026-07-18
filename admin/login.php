<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - API Dashboard</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Google Fonts (Material UI Typography) -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">

    <style>
        :root {
            /* Strict Palette Definitions */
            --primary-100: #FF6B6B;
            --primary-200: #dd4d51;
            --primary-300: #8f001a;
            --accent-100: #00FFFF;
            --accent-200: #00999b;
            --text-100: #FFFFFF;
            --text-200: #e0e0e0;
            --bg-100: #0F0F0F;
            --bg-200: #1f1f1f;
            --bg-300: #353535;
        }

        body {
            font-family: 'Roboto', sans-serif;
            background-color: var(--bg-100); /* 60% Rule: Dominant Dark Background */
            color: var(--text-100);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        /* --- PRELOADER --- */
        #preloader {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: var(--bg-100);
            z-index: 9999;
            display: flex;
            justify-content: center;
            align-items: center;
            transition: opacity 0.5s ease, visibility 0.5s ease;
        }
        .spinner {
            border: 4px solid var(--bg-300);
            border-top: 4px solid var(--accent-100);
            border-radius: 50%;
            width: 60px;
            height: 60px;
            animation: spin 1s linear infinite;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

        /* --- LAYOUT ANIMATION --- */
        .fade-in-up {
            animation: fadeInUp 0.8s cubic-bezier(0.2, 0.8, 0.2, 1) forwards;
            opacity: 0;
            transform: translateY(30px);
        }
        .delay-1 { animation-delay: 0.2s; }
        .delay-2 { animation-delay: 0.4s; }
        
        @keyframes fadeInUp { to { opacity: 1; transform: translateY(0); } }

        /* --- MATERIAL UI CARD --- */
        .login-card {
            background-color: var(--bg-200); /* 60% Rule: Secondary Background */
            border: none;
            border-radius: 16px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.6), 0 5px 15px rgba(0,0,0,0.4); /* Material Elevation */
            padding: 2.5rem;
            width: 100%;
            max-width: 450px;
            position: relative;
            overflow: hidden;
        }
        
        /* 30% Rule: Primary Color for structural highlights & branding */
        .login-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; width: 100%; height: 6px;
            background: linear-gradient(90deg, var(--primary-100), var(--primary-300));
        }

        .brand-icon {
            font-size: 3rem;
            color: var(--primary-100);
            margin-bottom: 1rem;
        }

        .card-title {
            font-weight: 700;
            color: var(--text-100);
            margin-bottom: 0.5rem;
        }
        .card-subtitle {
            color: var(--text-200);
            font-weight: 300;
            margin-bottom: 2rem;
        }

        /* --- MATERIAL INPUTS (Floating Labels) --- */
        .form-floating > .form-control {
            background-color: var(--bg-300);
            border: 2px solid transparent;
            color: var(--text-100);
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .form-floating > label {
            color: var(--text-200);
            transition: all 0.2s ease;
        }
        .form-floating > .form-control:focus {
            background-color: var(--bg-300);
            color: var(--text-100);
            border-color: var(--primary-100); /* 30% Rule: Focus state */
            box-shadow: none;
        }
        .form-floating > .form-control:focus ~ label,
        .form-floating > .form-control:not(:placeholder-shown) ~ label {
            color: var(--primary-100);
            transform: scale(0.85) translateY(-0.5rem) translateX(0.15rem);
        }

        /* Input Autocomplete fix for dark mode */
        input:-webkit-autofill,
        input:-webkit-autofill:hover, 
        input:-webkit-autofill:focus, 
        input:-webkit-autofill:active{
            -webkit-box-shadow: 0 0 0 30px var(--bg-300) inset !important;
            -webkit-text-fill-color: var(--text-100) !important;
            transition: background-color 5000s ease-in-out 0s;
        }

        /* --- 10% Rule: Accent Color for CTAs --- */
        .btn-accent {
            background-color: var(--accent-100);
            color: var(--bg-100);
            font-weight: 600;
            font-size: 1.1rem;
            padding: 12px;
            border: none;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 255, 255, 0.2);
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .btn-accent:hover {
            background-color: var(--accent-200);
            color: var(--text-100);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 255, 255, 0.4);
        }

        /* Checkbox Styling */
        .form-check-input {
            background-color: var(--bg-300);
            border-color: var(--text-200);
        }
        .form-check-input:checked {
            background-color: var(--primary-100);
            border-color: var(--primary-100);
        }
        .form-check-label {
            color: var(--text-200);
            font-weight: 300;
        }
        
        .forgot-link {
            color: var(--primary-100);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        .forgot-link:hover {
            color: var(--accent-100); /* Accent highlight on hover */
        }
        
        /* Password Toggle Icon */
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-200);
            cursor: pointer;
            z-index: 10;
        }
        .password-toggle:hover {
            color: var(--primary-100);
        }
    </style>
</head>
<body>

    <!-- Preloader -->
    <div id="preloader">
        <div class="spinner"></div>
    </div>

    <!-- Login Container -->
    <div class="container d-flex justify-content-center px-3">
        
        <div class="login-card fade-in-up">
            
            <div class="text-center fade-in-up delay-1">
                <i class="fa-solid fa-server brand-icon"></i>
                <h2 class="card-title">API Admin Access</h2>
                <p class="card-subtitle">Authenticate to manage dashboard metrics and routes.</p>
            </div>

            <!-- ALL POST Requests routed to index.php -->
            <form action="index.php" method="POST" class="fade-in-up delay-2">
                <!-- Action Identifier -->
                <input type="hidden" name="action" value="login">

                <!-- Username Input -->
                <div class="form-floating mb-4">
                    <input type="text" class="form-control" id="adminUsername" name="username" placeholder="Username" required>
                    <label for="adminUsername"><i class="fa-solid fa-user me-2"></i>Username</label>
                </div>

                <!-- Password Input -->
                <div class="form-floating mb-4 position-relative">
                    <input type="password" class="form-control" id="adminPassword" name="password" placeholder="Password" required>
                    <label for="adminPassword"><i class="fa-solid fa-lock me-2"></i>Password</label>
                    <i class="fa-solid fa-eye password-toggle" id="togglePassword" onclick="togglePasswordVisibility()"></i>
                </div>

                <!-- Remember Me & Forgot Password -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="remember_me" value="true" id="rememberMe">
                        <label class="form-check-label" for="rememberMe">
                            Remember me
                        </label>
                    </div>
                    <a href="index.php?action=forgot_password" class="forgot-link">Forgot Password?</a>
                </div>

                <!-- Submit CTA Button -->
                <button type="submit" class="btn btn-accent w-100 mt-2">
                    <i class="fa-solid fa-right-to-bracket me-2"></i> Secure Login
                </button>
            </form>

        </div>

    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom Interactivity Scripts -->
    <script>
        // 1. Preloader Functionality
        window.addEventListener('load', () => {
            const preloader = document.getElementById('preloader');
            // Artificial delay to ensure user sees system feedback (HCI principle)
            setTimeout(() => {
                preloader.style.opacity = '0';
                setTimeout(() => {
                    preloader.style.visibility = 'hidden';
                }, 500);
            }, 500); 
        });

        // 2. Password Visibility Toggle (HCI - Visibility of System Status & Control)
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('adminPassword');
            const toggleIcon = document.getElementById('togglePassword');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
                toggleIcon.style.color = 'var(--accent-100)'; // Feedback color
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
                toggleIcon.style.color = 'var(--text-200)';
            }
        }
    </script>
</body>
</html>