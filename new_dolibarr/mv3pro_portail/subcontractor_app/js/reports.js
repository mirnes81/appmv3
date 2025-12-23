const Reports = {
    photos: [],
    currentReport: null,

    init() {
        this.setupNewReportButton();
        this.setupReportForm();
        this.setupPhotoCapture();
        document.getElementById('reportDate').valueAsDate = new Date();
        this.calculateHoursWorked();
    },

    setupNewReportButton() {
        document.getElementById('newReportBtn').addEventListener('click', () => {
            this.showNewReportScreen();
        });

        document.getElementById('backFromReport').addEventListener('click', () => {
            this.showDashboardScreen();
        });

        document.getElementById('cancelReport').addEventListener('click', () => {
            if (confirm('Êtes-vous sûr de vouloir annuler ce rapport ?')) {
                this.resetReportForm();
                this.showDashboardScreen();
            }
        });
    },

    setupReportForm() {
        const form = document.getElementById('reportForm');
        const startTime = document.getElementById('startTime');
        const endTime = document.getElementById('endTime');
        const surfaceM2 = document.getElementById('surfaceM2');

        startTime.addEventListener('change', () => this.calculateHoursWorked());
        endTime.addEventListener('change', () => this.calculateHoursWorked());
        surfaceM2.addEventListener('input', () => this.calculateAmount());

        form.addEventListener('submit', (e) => {
            e.preventDefault();
            this.submitReport();
        });
    },

    calculateHoursWorked() {
        const startTime = document.getElementById('startTime').value;
        const endTime = document.getElementById('endTime').value;

        if (startTime && endTime) {
            const start = new Date(`2000-01-01 ${startTime}`);
            const end = new Date(`2000-01-01 ${endTime}`);
            const hours = (end - start) / (1000 * 60 * 60);

            if (hours > 0) {
                document.getElementById('hoursWorked').value = hours.toFixed(1);
            }
        }

        this.calculateAmount();
    },

    calculateAmount() {
        const user = Auth.getCurrentUser();
        if (!user) return;

        let amount = 0;

        if (user.rate_type === 'm2') {
            const m2 = parseFloat(document.getElementById('surfaceM2').value) || 0;
            amount = m2 * user.rate_amount;
        } else if (user.rate_type === 'hourly') {
            const hours = parseFloat(document.getElementById('hoursWorked').value) || 0;
            amount = hours * user.rate_amount;
        } else if (user.rate_type === 'daily') {
            amount = user.rate_amount;
        }

        document.getElementById('calculatedAmount').textContent = amount.toFixed(2) + ' €';
    },

    setupPhotoCapture() {
        const addPhotoBtn = document.getElementById('addPhotoBtn');
        const photoInput = document.getElementById('photoInput');

        addPhotoBtn.addEventListener('click', () => {
            photoInput.click();
        });

        photoInput.addEventListener('change', async (e) => {
            const file = e.target.files[0];
            if (file) {
                await this.addPhoto(file);
            }
            photoInput.value = '';
        });
    },

    async addPhoto(file) {
        App.showLoader();

        try {
            const position = await this.getPosition();

            const reader = new FileReader();
            reader.onload = (e) => {
                const photo = {
                    id: Date.now(),
                    data: e.target.result,
                    name: file.name,
                    size: file.size,
                    latitude: position?.latitude || null,
                    longitude: position?.longitude || null,
                    date: new Date().toISOString()
                };

                this.photos.push(photo);
                this.renderPhotos();
                App.hideLoader();
                App.showToast('Photo ajoutée');
            };

            reader.readAsDataURL(file);
        } catch (error) {
            console.error('Error adding photo:', error);
            App.hideLoader();
            App.showToast('Erreur lors de l\'ajout de la photo');
        }
    },

    getPosition() {
        return new Promise((resolve) => {
            if ('geolocation' in navigator) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        resolve({
                            latitude: position.coords.latitude,
                            longitude: position.coords.longitude
                        });
                    },
                    () => resolve(null),
                    { timeout: 5000 }
                );
            } else {
                resolve(null);
            }
        });
    },

    renderPhotos() {
        const photoGrid = document.getElementById('photoGrid');
        const addButton = photoGrid.querySelector('.photo-add');

        photoGrid.querySelectorAll('.photo-item').forEach(item => item.remove());

        this.photos.forEach((photo, index) => {
            const photoItem = document.createElement('div');
            photoItem.className = 'photo-item';
            photoItem.innerHTML = `
                <img src="${photo.data}" alt="Photo ${index + 1}">
                <button class="photo-remove" data-id="${photo.id}">×</button>
            `;

            photoGrid.insertBefore(photoItem, addButton);

            photoItem.querySelector('.photo-remove').addEventListener('click', () => {
                this.removePhoto(photo.id);
            });
        });

        document.getElementById('photoCountText').textContent = `${this.photos.length} photo(s)`;
    },

    removePhoto(id) {
        this.photos = this.photos.filter(p => p.id !== id);
        this.renderPhotos();
        App.showToast('Photo supprimée');
    },

    async submitReport() {
        if (this.photos.length < 3) {
            App.showToast('Minimum 3 photos requises');
            return;
        }

        if (!SignatureManager.hasSignature()) {
            App.showToast('Signature requise');
            return;
        }

        App.showLoader();

        try {
            const position = await this.getPosition();

            const formData = {
                session_token: Auth.getSessionToken(),
                report_date: document.getElementById('reportDate').value,
                work_type: document.getElementById('workType').value,
                start_time: document.getElementById('startTime').value,
                end_time: document.getElementById('endTime').value,
                surface_m2: parseFloat(document.getElementById('surfaceM2').value) || 0,
                hours_worked: parseFloat(document.getElementById('hoursWorked').value) || 0,
                notes: document.getElementById('notes').value,
                latitude: position?.latitude || null,
                longitude: position?.longitude || null,
                signature_data: SignatureManager.getSignature(),
                photos: this.photos
            };

            const response = await fetch('../api/subcontractor_submit_report.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            });

            const result = await response.json();

            if (result.success) {
                App.showToast('Rapport soumis avec succès !');
                this.resetReportForm();
                this.showDashboardScreen();
                this.loadDashboardData();
            } else {
                App.showToast(result.message || 'Erreur lors de la soumission');
            }
        } catch (error) {
            console.error('Submit error:', error);
            App.showToast('Erreur de connexion');
        } finally {
            App.hideLoader();
        }
    },

    resetReportForm() {
        document.getElementById('reportForm').reset();
        document.getElementById('reportDate').valueAsDate = new Date();
        this.photos = [];
        this.renderPhotos();
        SignatureManager.clear();
        document.getElementById('calculatedAmount').textContent = '0.00 €';
    },

    showNewReportScreen() {
        document.getElementById('dashboardScreen').classList.remove('active');
        document.getElementById('newReportScreen').classList.add('active');
    },

    showDashboardScreen() {
        document.getElementById('newReportScreen').classList.remove('active');
        document.getElementById('dashboardScreen').classList.add('active');
    },

    async loadDashboardData() {
        try {
            const today = new Date();
            document.getElementById('todayDate').textContent = today.toLocaleDateString('fr-FR', {
                day: 'numeric',
                month: 'short'
            });

            const response = await fetch('../api/subcontractor_dashboard.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    session_token: Auth.getSessionToken()
                })
            });

            const result = await response.json();

            if (result.success) {
                document.getElementById('reportCount').textContent = result.data.month_reports;
                document.getElementById('totalM2').textContent = `${result.data.total_m2} m²`;

                if (result.data.today_report) {
                    document.getElementById('todayReportStatus').innerHTML = `
                        <div style="display: flex; align-items: center; gap: 0.5rem; color: var(--success);">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                            <span>Rapport d'aujourd'hui soumis</span>
                        </div>
                    `;
                    document.getElementById('newReportBtn').disabled = true;
                } else {
                    document.getElementById('todayReportStatus').innerHTML = `
                        <div style="color: var(--warning);">
                            ⚠️ Aucun rapport aujourd'hui
                        </div>
                    `;
                    document.getElementById('newReportBtn').disabled = false;
                }

                this.renderRecentReports(result.data.recent_reports);
            }
        } catch (error) {
            console.error('Error loading dashboard:', error);
        }
    },

    renderRecentReports(reports) {
        const container = document.getElementById('recentReports');

        if (!reports || reports.length === 0) {
            container.innerHTML = '<div class="loading">Aucun rapport récent</div>';
            return;
        }

        container.innerHTML = reports.map(report => {
            const statusClass = {
                0: 'status-draft',
                1: 'status-submitted',
                2: 'status-validated'
            }[report.status] || 'status-draft';

            const statusLabel = {
                0: 'Brouillon',
                1: 'Soumis',
                2: 'Validé'
            }[report.status] || 'Inconnu';

            return `
                <div class="report-item" data-id="${report.rowid}">
                    <div class="report-header">
                        <span class="report-ref">${report.ref}</span>
                        <span class="report-status-badge ${statusClass}">${statusLabel}</span>
                    </div>
                    <div class="report-details">
                        <span>📅 ${new Date(report.report_date).toLocaleDateString('fr-FR')}</span>
                        <span>📐 ${report.surface_m2} m²</span>
                        <span>💰 ${report.amount_calculated} €</span>
                    </div>
                </div>
            `;
        }).join('');
    }
};
