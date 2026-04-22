<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - LocalPhone Marketplace</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="navbar-container container">
            <a href="../index.php" class="navbar-logo">Local<span>Phone</span></a>
            <ul class="navbar-menu">
                <li><a href="../index.php">Home</a></li>
                <li><a href="products.php">Browse Phones</a></li>
                <li><a href="login.php" class="btn btn-primary">Login</a></li>
            </ul>
        </div>
    </nav>

    <main class="container" style="max-width: 600px; margin: 3rem auto;">
        <h1 class="text-center mb-3">Create Account</h1>
        
        <div class="product-card">
            <form id="registerForm" class="p-2">
                <div class="form-group">
                    <label for="registerName">Full Name / Shop Name</label>
                    <input type="text" id="registerName" name="name" required placeholder="Enter your name">
                </div>
                
                <div class="form-group">
                    <label for="registerEmail">Email Address</label>
                    <input type="email" id="registerEmail" name="email" required placeholder="Enter your email">
                </div>
                
                <div class="form-group">
                    <label for="registerPassword">Password</label>
                    <input type="password" id="registerPassword" name="password" required placeholder="Create a password (min 6 characters)" minlength="6">
                </div>
                
                <div class="form-group">
                    <label for="registerConfirmPassword">Confirm Password</label>
                    <input type="password" id="registerConfirmPassword" name="confirm_password" required placeholder="Confirm your password">
                </div>
                
                <div class="form-group">
                    <label for="registerRole">I want to:</label>
                    <select id="registerRole" name="role" required onchange="toggleShopFields()">
                        <option value="customer">Buy Phones (Customer)</option>
                        <option value="shop">Sell Phones (Shop Owner)</option>
                    </select>
                </div>
                
                <!-- Shop-specific fields -->
                <div id="shopFields" style="display: none;">
                    <div class="form-group">
                        <label for="shopName">Shop Name</label>
                        <input type="text" id="shopName" name="shop_name" placeholder="Enter your shop name">
                    </div>
                    
                    <div class="form-group">
                        <label for="shopAddress">Shop Address</label>
                        <textarea id="shopAddress" name="shop_address" placeholder="Enter your shop address"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="shopPhone">Shop Phone Number</label>
                        <input type="tel" id="shopPhone" name="shop_phone" placeholder="Enter shop contact number">
                    </div>
                </div>
                
                <button type="submit" class="btn btn-secondary" style="width: 100%;">Create Account</button>
            </form>
            
            <div class="mt-2" style="text-align: center;">
                <p>Already have an account? <a href="login.php">Login here</a></p>
            </div>
        </div>
    </main>

    <script>
        function toggleShopFields() {
            const role = document.getElementById('registerRole').value;
            const shopFields = document.getElementById('shopFields');
            shopFields.style.display = role === 'shop' ? 'block' : 'none';
            
            // Make shop fields required only for shop role
            document.getElementById('shopName').required = role === 'shop';
            document.getElementById('shopAddress').required = role === 'shop';
            document.getElementById('shopPhone').required = role === 'shop';
        }
    </script>
    <script src="../assets/js/main.js"></script>
</body>
</html>