<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Connect - Register</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/register_style.css">
    <style>
        /* ADDED: Back Button Styles */
        .header-container { display: flex; align-items: center; gap: 15px; margin-bottom: 20px; }
        .btn-back { text-decoration: none; color: #65676b; font-size: 1.2rem; transition: 0.2s; cursor: pointer; border: none; background: none; }
        .btn-back:hover { color: #1877f2; }
    </style>
</head>
<body class="auth-page">

<div class="register-container">
    <div class="header-container">
        <a href="../index.php" class="btn-back">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
        <h2 style="margin: 0;">Join Our Community</h2>
    </div>

    <form action="process_register.php" method="POST" id="registrationForm">
        <div class="form-grid">
            
            <div class="form-column">
                <label>Username</label>
                <input type="text" name="username" placeholder="Username" required>
                
                <label>Email Address</label>
                <input type="email" name="email" id="email_input" placeholder="Email Address" required 
                       pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$">
                
                <label>Contact Number (Mobile)</label>
                <div class="input-container">
                    <span class="fixed-prefix">09</span>
                    <input type="text" 
                        id="contact_no_input" 
                        placeholder="123456789" 
                        maxlength="9" 
                        autocomplete="off"
                        required>
                    <input type="hidden" name="contact_no" id="full_contact_no">
                </div>

                <label>Password</label>
                <div class="password-wrapper">
                    <input type="password" id="password" name="password" placeholder="Password" required>
                    <i class="fa-solid fa-eye toggle-eye" id="eye-password" onclick="togglePassword('password', 'eye-password')"></i>
                </div>

                <label>Confirm Password</label>
                <div class="password-wrapper">
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required>
                    <i class="fa-solid fa-eye toggle-eye" id="eye-confirm" onclick="togglePassword('confirm_password', 'eye-confirm')"></i>
                </div>
                
                <div id="message" style="font-size: 0.8em; min-height: 1.2em;"></div>
            </div>

            <div class="form-column">
                <div style="display: flex; gap: 10px;">
                    <div style="flex: 1;">
                        <label>First Name</label>
                        <input type="text" name="first_name" placeholder="First Name" required>
                    </div>
                    <div style="flex: 1;">
                        <label>Last Name</label>
                        <input type="text" name="last_name" placeholder="Last Name" required>
                    </div>
                </div>

                <label>Birthdate</label>
                <input type="date" name="birthdate" required>
                
                <label>Gender</label>
                <select name="gender" required>
                    <option value="">Select Gender</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                </select>

                <label>Civil Status</label>
                <select name="civil_status" required>
                    <option value="">Select Civil Status</option>
                    <option value="Single">Single</option>
                    <option value="Married">Married</option>
                    <option value="Widowed">Widowed</option>
                    <option value="Separated">Separated</option>
                </select>

                <label>Location Details</label>
                <select id="region" name="region" required><option value="">Select Region</option></select>
                <select id="province" name="province" required disabled><option value="">Select Province</option></select>
                <select id="city" name="city" required disabled><option value="">Select City</option></select>
                <select id="barangay" name="barangay" required disabled><option value="">Select Barangay</option></select>
                
                <select id="purok" name="purok" required disabled>
                    <option value="">Select Purok</option>
                    <option value="Purok 1">Purok 1</option>
                    <option value="Purok 2">Purok 2</option>
                    <option value="Purok 3">Purok 3</option>
                    <option value="Purok 4">Purok 4</option>
                    <option value="Purok 5">Purok 5</option>
                    <option value="Purok 6">Purok 6</option>
                    <option value="Purok 7">Purok 7</option>
                </select>
            </div>

            <button type="submit" class="btn-secondary">Create Account</button>
        </div>
    </form>
</div>

<script>
    const regionSelect = document.getElementById('region');
    const provinceSelect = document.getElementById('province');
    const citySelect = document.getElementById('city');
    const brgySelect = document.getElementById('barangay');
    const purokSelect = document.getElementById('purok');
    const contactInput = document.getElementById('contact_no_input');
    const hiddenInput = document.getElementById('full_contact_no');

    // --- 1. PSGC API CASCADE LOGIC (Restored) ---
    fetch('https://psgc.gitlab.io/api/regions/')
        .then(res => res.json())
        .then(data => {
            data.sort((a, b) => a.name.localeCompare(b.name));
            data.forEach(item => {
                let opt = new Option(item.name, item.name);
                opt.dataset.code = item.code; 
                regionSelect.add(opt);
            });
        });

    const updateDropdown = (url, target, nextDropdowns = []) => {
        target.disabled = false;
        target.innerHTML = '<option value="">Loading...</option>';
        nextDropdowns.forEach(d => { 
            if(d.id === 'purok') { d.selectedIndex = 0; } 
            else { d.innerHTML = '<option value="">Select...</option>'; }
            d.disabled = true; 
        });

        fetch(url)
            .then(res => res.json())
            .then(data => {
                target.innerHTML = '<option value="">Select Name</option>';
                data.sort((a, b) => a.name.localeCompare(b.name));
                data.forEach(item => {
                    let opt = new Option(item.name, item.name);
                    opt.dataset.code = item.code;
                    target.add(opt);
                });
            });
    };

    regionSelect.addEventListener('change', function() {
        const code = this.options[this.selectedIndex].dataset.code;
        if(code) updateDropdown(`https://psgc.gitlab.io/api/regions/${code}/provinces/`, provinceSelect, [citySelect, brgySelect, purokSelect]);
    });

    provinceSelect.addEventListener('change', function() {
        const code = this.options[this.selectedIndex].dataset.code;
        if(code) updateDropdown(`https://psgc.gitlab.io/api/provinces/${code}/cities-municipalities/`, citySelect, [brgySelect, purokSelect]);
    });

    citySelect.addEventListener('change', function() {
        const code = this.options[this.selectedIndex].dataset.code;
        if(code) updateDropdown(`https://psgc.gitlab.io/api/cities-municipalities/${code}/barangays/`, brgySelect, [purokSelect]);
    });

    brgySelect.addEventListener('change', function() {
        purokSelect.disabled = !(this.value !== "" && this.value !== "Select Name");
    });

    // --- 2. PASSWORD & CONTACT LOGIC (Restored) ---
    function togglePassword(inputId, eyeId) {
        const input = document.getElementById(inputId);
        const eyeIcon = document.getElementById(eyeId);
        if (input.type === "password") {
            input.type = "text";
            eyeIcon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            input.type = "password";
            eyeIcon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    }

    contactInput.addEventListener('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
        hiddenInput.value = "09" + this.value;
    });

    function validateForm() {
        const pass = document.getElementById('password').value;
        const confirm = document.getElementById('confirm_password').value;
        const contactBody = contactInput.value;
        const emailBody = document.getElementById('email_input').value; // Added
        const msg = document.getElementById('message');
        const strongRegex = /^(?=.*[A-Z])(?=.*[!@#$%^&*])(?=.{8,})/;
        const emailRegex = /^[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$/i; // Added

        // ADDED: Validate Email Legitimacy
        if (!emailRegex.test(emailBody)) {
            alert("Please enter a legitimate email address for confirmation.");
            return false;
        }

        if (contactBody.length !== 9) {
            alert("Please enter exactly 9 digits after the 09 prefix.");
            return false;
        }

        if (!strongRegex.test(pass)) {
            msg.style.color = "red";
            msg.innerHTML = "❌ Min 8 chars, 1 Capital, 1 Special Char.";
            return false;
        }

        if (pass !== confirm) {
            msg.style.color = "red";
            msg.innerHTML = "❌ Passwords do not match.";
            return false;
        }

        hiddenInput.value = "09" + contactBody;
        return true;
    }

    document.getElementById('registrationForm').onsubmit = function() {
        return validateForm();
    };

    document.getElementById('password').onkeyup = function() {
        const pass = this.value;
        const msg = document.getElementById('message');
        const strongRegex = /^(?=.*[A-Z])(?=.*[!@#$%^&*])(?=.{8,})/;
        if (!pass) { msg.innerHTML = ""; }
        else if (!strongRegex.test(pass)) {
            msg.style.color = "red";
            msg.innerHTML = "❌ Min 8 chars, 1 Capital, 1 Special Char.";
        } else {
            msg.style.color = "green";
            msg.innerHTML = "✅ Password strength: Strong";
        }
    };
</script>
</body>
</html>