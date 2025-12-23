const Auth = {
    currentUser: null,
    sessionToken: null,

    init() {
        this.checkSession();
        this.setupLoginForm();
    },

    checkSession() {
        const token = localStorage.getItem('subcontractor_session');
        if (token) {
            this.verifySession(token);
        }
    },

    async verifySession(token) {
        try {
            const response = await fetch('../api/subcontractor_verify_session.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ session_token: token })
            });

            const result = await response.json();

            if (result.success) {
                this.currentUser = result.user;
                this.sessionToken = token;
                this.showDashboard();
            } else {
                localStorage.removeItem('subcontractor_session');
            }
        } catch (error) {
            console.error('Session verification failed:', error);
            localStorage.removeItem('subcontractor_session');
        }
    },

    setupLoginForm() {
        const pinInputs = document.querySelectorAll('.pin-digit');
        const loginBtn = document.getElementById('loginBtn');

        pinInputs.forEach((input, index) => {
            input.addEventListener('input', (e) => {
                if (e.target.value.length === 1 && index < pinInputs.length - 1) {
                    pinInputs[index + 1].focus();
                }

                if (index === pinInputs.length - 1 && e.target.value.length === 1) {
                    this.attemptLogin();
                }
            });

            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && !e.target.value && index > 0) {
                    pinInputs[index - 1].focus();
                }
            });
        });

        loginBtn.addEventListener('click', () => this.attemptLogin());

        document.getElementById('logoutBtn').addEventListener('click', () => this.logout());
    },

    async attemptLogin() {
        const pinInputs = document.querySelectorAll('.pin-digit');
        const pin = Array.from(pinInputs).map(input => input.value).join('');
        const errorDiv = document.getElementById('loginError');

        if (pin.length !== 4) {
            errorDiv.textContent = 'Veuillez saisir un code PIN à 4 chiffres';
            return;
        }

        App.showLoader();
        errorDiv.textContent = '';

        try {
            const response = await fetch('../api/subcontractor_login.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    pin_code: pin,
                    device_info: navigator.userAgent
                })
            });

            const result = await response.json();

            if (result.success) {
                this.currentUser = result.user;
                this.sessionToken = result.session_token;
                localStorage.setItem('subcontractor_session', result.session_token);

                pinInputs.forEach(input => input.value = '');

                await this.updateLastLogin();

                this.showDashboard();
                App.showToast('Connexion réussie !');
            } else {
                errorDiv.textContent = result.message || 'Code PIN incorrect';
                pinInputs[0].focus();
            }
        } catch (error) {
            console.error('Login error:', error);
            errorDiv.textContent = 'Erreur de connexion. Veuillez réessayer.';
        } finally {
            App.hideLoader();
        }
    },

    async updateLastLogin() {
        try {
            await fetch('../api/subcontractor_update_activity.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    session_token: this.sessionToken
                })
            });
        } catch (error) {
            console.error('Failed to update last login:', error);
        }
    },

    showDashboard() {
        document.getElementById('loginScreen').classList.remove('active');
        document.getElementById('dashboardScreen').classList.add('active');

        document.getElementById('userName').textContent =
            `${this.currentUser.firstname} ${this.currentUser.lastname}`;
        document.getElementById('userSpecialty').textContent = this.currentUser.specialty || '';
        document.getElementById('userAvatar').textContent =
            `${this.currentUser.firstname.charAt(0)}${this.currentUser.lastname.charAt(0)}`;

        Reports.loadDashboardData();
    },

    logout() {
        if (confirm('Êtes-vous sûr de vouloir vous déconnecter ?')) {
            localStorage.removeItem('subcontractor_session');
            this.currentUser = null;
            this.sessionToken = null;

            document.getElementById('dashboardScreen').classList.remove('active');
            document.getElementById('loginScreen').classList.add('active');

            document.querySelectorAll('.pin-digit').forEach(input => input.value = '');
            document.querySelectorAll('.pin-digit')[0].focus();

            App.showToast('Déconnexion réussie');
        }
    },

    getSessionToken() {
        return this.sessionToken;
    },

    getCurrentUser() {
        return this.currentUser;
    }
};
