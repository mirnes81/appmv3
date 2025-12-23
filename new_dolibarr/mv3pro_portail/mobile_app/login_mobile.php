<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#0891b2">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>Connexion - MV3 PRO Mobile</title>
    <link rel="manifest" href="manifest.json">
    <link rel="stylesheet" href="css/mobile_app.css">
    <style>
        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            z-index: 10000;
            align-items: center;
            justify-content: center;
        }
        .loading-overlay.active {
            display: flex;
        }
        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 4px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>

    <div class="pull-to-refresh">
        <div class="spinner"></div>
    </div>

    <div class="login-container">
        <div class="login-logo">
            🏗️
        </div>

        <div class="login-card">
            <h1 class="login-title">MV3 PRO Mobile</h1>
            <p class="login-subtitle">Connexion employé</p>

            <div id="errorMessage" class="alert alert-error" style="display:none"></div>

            <form id="loginForm" autocomplete="on">
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" id="email" name="email" class="form-input"
                           placeholder="votre.email@exemple.com" required autofocus autocomplete="email">
                </div>

                <div class="form-group">
                    <label class="form-label">Mot de passe</label>
                    <input type="password" id="password" name="password" class="form-input"
                           placeholder="••••••••" required autocomplete="current-password">
                </div>

                <div class="form-group">
                    <label style="display:flex;align-items:center;gap:8px;font-size:14px;color:var(--text-secondary)">
                        <input type="checkbox" id="rememberMe" name="rememberMe" style="width:18px;height:18px">
                        Se souvenir de moi
                    </label>
                </div>

                <button type="submit" class="btn btn-primary">
                    <span>Se connecter</span>
                    <span>→</span>
                </button>
            </form>

            <div style="text-align:center;margin-top:24px;font-size:12px;color:var(--text-light)">
                <p>Authentification sécurisée indépendante</p>
                <p style="margin-top:8px">Demandez vos identifiants à votre responsable</p>
            </div>
        </div>
    </div>

    <script>
        const API_URL = '/custom/mv3pro_portail/mobile_app/api/auth.php';

        // Vérifier si déjà connecté
        window.addEventListener('DOMContentLoaded', () => {
            const token = localStorage.getItem('mobile_auth_token');
            if (token) {
                verifyToken(token);
            }

            // Remplir email si sauvegardé
            const savedEmail = localStorage.getItem('mobile_saved_email');
            if (savedEmail) {
                document.getElementById('email').value = savedEmail;
                document.getElementById('rememberMe').checked = true;
            }
        });

        async function verifyToken(token) {
            try {
                const response = await fetch(`${API_URL}?action=verify&token=${token}`);
                const data = await response.json();

                if (data.success) {
                    // Token valide, rediriger vers dashboard
                    window.location.href = 'dashboard_mobile.php';
                } else {
                    // Token invalide, le supprimer
                    localStorage.removeItem('mobile_auth_token');
                    localStorage.removeItem('mobile_user_data');
                }
            } catch (error) {
                console.error('Error verifying token:', error);
                localStorage.removeItem('mobile_auth_token');
                localStorage.removeItem('mobile_user_data');
            }
        }

        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const rememberMe = document.getElementById('rememberMe').checked;

            const errorDiv = document.getElementById('errorMessage');
            errorDiv.style.display = 'none';

            if (!email || !password) {
                showError('Veuillez remplir tous les champs');
                return;
            }

            showLoading(true);

            try {
                const response = await fetch(`${API_URL}?action=login`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ email, password })
                });

                const data = await response.json();

                if (data.success) {
                    // Sauvegarder le token
                    localStorage.setItem('mobile_auth_token', data.token);
                    localStorage.setItem('mobile_user_data', JSON.stringify(data.user));

                    // Sauvegarder l'email si demandé
                    if (rememberMe) {
                        localStorage.setItem('mobile_saved_email', email);
                    } else {
                        localStorage.removeItem('mobile_saved_email');
                    }

                    // Rediriger vers dashboard
                    window.location.href = 'dashboard_mobile.php';
                } else {
                    showError(data.error || 'Erreur de connexion');
                }
            } catch (error) {
                console.error('Login error:', error);
                showError('Erreur de connexion au serveur');
            } finally {
                showLoading(false);
            }
        });

        function showError(message) {
            const errorDiv = document.getElementById('errorMessage');
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';

            // Auto-hide after 5 seconds
            setTimeout(() => {
                errorDiv.style.display = 'none';
            }, 5000);
        }

        function showLoading(show) {
            const overlay = document.getElementById('loadingOverlay');
            overlay.classList.toggle('active', show);
        }

        // PWA Install prompt
        let deferredPrompt;
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;

            const installBtn = document.createElement('button');
            installBtn.className = 'btn btn-primary';
            installBtn.style.cssText = 'margin-top:16px;background:linear-gradient(135deg,#10b981 0%,#059669 100%)';
            installBtn.innerHTML = '<span>📱 Installer l\'application</span>';
            installBtn.onclick = async () => {
                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;
                deferredPrompt = null;
                installBtn.remove();
            };
            document.querySelector('.login-card').appendChild(installBtn);
        });
    </script>
</body>
</html>
