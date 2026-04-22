<?php
session_start();
require_once '../includes/auth.php';

redirectIfLoggedIn();
$message = getMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - LocalPhone Marketplace</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .debug-panel {
            background: #fff3cd;
            border: 2px solid #ffc107;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
            font-family: monospace;
            font-size: 12px;
            display: none !important;
        }
        .debug-panel h4 {
            margin: 0 0 10px 0;
            color: #856404;
        }
        .debug-panel pre {
            background: #f8f9fa;
            padding: 10px;
            overflow-x: auto;
            margin: 5px 0;
        }
        .error-panel {
            background: #f8d7da;
            border: 2px solid #dc3545;
            color: #721c24;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="navbar-container container">
            <a href="../index.php" class="navbar-logo">Local<span>Phone</span></a>
            <ul class="navbar-menu">
                <li><a href="../index.php">Home</a></li>
                <li><a href="products.php">Browse Phones</a></li>
                <li><a href="register.php" class="btn btn-secondary">Register</a></li>
            </ul>
        </div>
    </nav>

    <main class="container" style="max-width: 500px; margin: 3rem auto;">
        <h1 class="text-center mb-3">Welcome Back</h1>
        
        <!-- Debug Panel -->
        <div id="debugPanel" class="debug-panel">
            <h4>🐛 Debug Information</h4>
            <div id="debugContent"></div>
        </div>

        <!-- Error Panel -->
        <div id="errorPanel" class="error-panel" style="display: none;">
            <h4>❌ Login Error</h4>
            <div id="errorContent"></div>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message['type']; ?>">
                <?php echo htmlspecialchars($message['message']); ?>
            </div>
        <?php endif; ?>

        <div class="product-card">
            <form id="loginForm" class="p-2">
                <div class="form-group">
                    <label for="loginEmail">Email Address</label>
                    <input type="email" id="loginEmail" name="email" required placeholder="Enter your email" value="">
                </div>
                
                <div class="form-group">
                    <label for="loginPassword">Password</label>
                    <input type="password" id="loginPassword" name="password" required placeholder="Enter your password">
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;" id="loginBtn">
                    <span id="btnText">Login</span>
                </button>
            </form>
            
            <div class="mt-2" style="text-align: center;">
                <p>Don't have an account? <a href="register.php">Register here</a></p>
            </div>
        </div>
    </main>

    <script>
        // Standalone Login JavaScript - No dependencies on main.js
        console.log('=== Login Page Standalone Script Loaded ===');
        
        // API base URL
        const API_BASE = '../api/';
        
        // Debug logging function (logs to console only, keeps panel hidden)
        function logDebug(message, data) {
            console.log('[Login Debug]', message, data);
        }
        
        // Show error function
        function showError(message, details) {
            const errorPanel = document.getElementById('errorPanel');
            const errorContent = document.getElementById('errorContent');
            
            errorPanel.style.display = 'block';
            errorContent.innerHTML = `<p><strong>${message}</strong></p>`;
            
            if (details) {
                errorContent.innerHTML += `<pre style="background: #fff; padding: 10px; margin-top: 10px;">${details}</pre>`;
            }
        }
        
        // Hide error function
        function hideError() {
            document.getElementById('errorPanel').style.display = 'none';
        }
        
        // Handle login form submission
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            hideError();
            
            const email = document.getElementById('loginEmail').value.trim();
            const password = document.getElementById('loginPassword').value;
            const btn = document.getElementById('loginBtn');
            const btnText = document.getElementById('btnText');
            
            logDebug('Login attempt started', { email: email });
            
            if (!email || !password) {
                showError('Please enter both email and password');
                return;
            }
            
            // Disable button during login
            btn.disabled = true;
            btnText.textContent = 'Logging in...';
            
            try {
                const url = API_BASE + 'login.php';
                logDebug('Sending request to', url);
                
                const requestData = { email: email, password: password };
                logDebug('Request data', requestData);
                
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(requestData)
                });
                
                logDebug('Response received', {
                    status: response.status,
                    statusText: response.statusText,
                    headers: Object.fromEntries(response.headers.entries())
                });
                
                if (!response.ok) {
                    throw new Error('HTTP error! Status: ' + response.status + ' ' + response.statusText);
                }
                
                const contentType = response.headers.get('content-type');
                logDebug('Content-Type', contentType);
                
                if (!contentType || !contentType.includes('application/json')) {
                    const textResponse = await response.text();
                    logDebug('Non-JSON response received', textResponse.substring(0, 500));
                    throw new Error('Server returned non-JSON response. Content-Type: ' + contentType);
                }
                
                const data = await response.json();
                logDebug('Response data', data);
                
                if (data.success) {
                    logDebug('Login successful! Redirecting to', data.redirect);
                    btnText.textContent = 'Success! Redirecting...';
                    window.location.href = data.redirect || '../index.php';
                } else {
                    showError(data.message || 'Login failed', 'Server response: ' + JSON.stringify(data, null, 2));
                    btn.disabled = false;
                    btnText.textContent = 'Login';
                }
            } catch (error) {
                logDebug('Login error caught', {
                    message: error.message,
                    stack: error.stack
                });
                
                showError(
                    'Error logging in: ' + error.message,
                    'Stack trace:\n' + error.stack
                );
                
                btn.disabled = false;
                btnText.textContent = 'Login';
            }
        });
        
        // Log to console only (debug panel hidden)
        console.log('Login page loaded', {
            url: window.location.href,
            apiBase: API_BASE
        });
    </script>
</body>
</html>
