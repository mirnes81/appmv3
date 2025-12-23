<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Sous-Traitants MV3 PRO</title>
    <meta name="theme-color" content="#2563eb">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="manifest" href="manifest.json">
    <link rel="stylesheet" href="css/app.css">
</head>
<body>
    <div id="app">
        <div id="loginScreen" class="screen active">
            <div class="login-container">
                <div class="logo-section">
                    <div class="logo-circle">
                        <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </div>
                    <h1>Sous-Traitants</h1>
                    <p>MV3 PRO</p>
                </div>

                <div class="pin-section">
                    <h2>Code PIN</h2>
                    <div class="pin-inputs">
                        <input type="tel" maxlength="1" class="pin-digit" data-index="0" />
                        <input type="tel" maxlength="1" class="pin-digit" data-index="1" />
                        <input type="tel" maxlength="1" class="pin-digit" data-index="2" />
                        <input type="tel" maxlength="1" class="pin-digit" data-index="3" />
                    </div>
                    <div id="loginError" class="error-message"></div>
                    <button id="loginBtn" class="btn-primary">
                        <span>Connexion</span>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path d="M5 12h14m-7-7l7 7-7 7"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <div id="dashboardScreen" class="screen">
            <div class="header">
                <div class="user-info">
                    <div class="user-avatar" id="userAvatar"></div>
                    <div>
                        <h3 id="userName">Chargement...</h3>
                        <p id="userSpecialty"></p>
                    </div>
                </div>
                <button id="logoutBtn" class="btn-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                        <polyline points="16 17 21 12 16 7"/>
                        <line x1="21" y1="12" x2="9" y2="12"/>
                    </svg>
                </button>
            </div>

            <div class="content">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">📅</div>
                        <div class="stat-value" id="todayDate"></div>
                        <div class="stat-label">Aujourd'hui</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">📋</div>
                        <div class="stat-value" id="reportCount">0</div>
                        <div class="stat-label">Rapports ce mois</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">📐</div>
                        <div class="stat-value" id="totalM2">0 m²</div>
                        <div class="stat-label">Total m² posés</div>
                    </div>
                </div>

                <div class="section">
                    <h2>Rapport du jour</h2>
                    <div id="todayReportStatus" class="report-status"></div>
                    <button id="newReportBtn" class="btn-primary btn-large">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path d="M12 5v14m-7-7h14"/>
                        </svg>
                        <span>Nouveau Rapport Journalier</span>
                    </button>
                </div>

                <div class="section">
                    <h2>Mes derniers rapports</h2>
                    <div id="recentReports" class="reports-list">
                        <div class="loading">Chargement...</div>
                    </div>
                </div>
            </div>

            <div class="bottom-nav">
                <button class="nav-item active" data-screen="dashboard">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                    </svg>
                    <span>Accueil</span>
                </button>
                <button class="nav-item" data-screen="reports">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                    </svg>
                    <span>Rapports</span>
                </button>
                <button class="nav-item" data-screen="profile">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                    <span>Profil</span>
                </button>
            </div>
        </div>

        <div id="newReportScreen" class="screen">
            <div class="header">
                <button id="backFromReport" class="btn-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="M19 12H5m7-7l-7 7 7 7"/>
                    </svg>
                </button>
                <h2>Nouveau Rapport</h2>
            </div>

            <div class="content">
                <form id="reportForm">
                    <div class="form-section">
                        <h3>Informations générales</h3>

                        <div class="form-group">
                            <label>Date <span class="required">*</span></label>
                            <input type="date" id="reportDate" required />
                        </div>

                        <div class="form-group">
                            <label>Type de travail <span class="required">*</span></label>
                            <select id="workType" required>
                                <option value="">Sélectionner...</option>
                                <option value="Pose carrelage sol">Pose carrelage sol</option>
                                <option value="Pose carrelage mural">Pose carrelage mural</option>
                                <option value="Pose faïence">Pose faïence</option>
                                <option value="Joints">Joints</option>
                                <option value="Ragréage">Ragréage</option>
                                <option value="Autre">Autre</option>
                            </select>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Heure début <span class="required">*</span></label>
                                <input type="time" id="startTime" required />
                            </div>
                            <div class="form-group">
                                <label>Heure fin <span class="required">*</span></label>
                                <input type="time" id="endTime" required />
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>Quantités</h3>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Surface (m²) <span class="required">*</span></label>
                                <input type="number" step="0.01" id="surfaceM2" required />
                            </div>
                            <div class="form-group">
                                <label>Heures travaillées</label>
                                <input type="number" step="0.5" id="hoursWorked" />
                            </div>
                        </div>

                        <div class="amount-display" id="amountDisplay">
                            <span>Montant calculé:</span>
                            <strong id="calculatedAmount">0.00 €</strong>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>Photos <span class="badge-required">Min. 3 requis</span></h3>
                        <div class="photo-info">
                            📸 Prenez des photos avant, pendant et après le travail
                        </div>

                        <div class="photo-grid" id="photoGrid">
                            <button type="button" class="photo-add" id="addPhotoBtn">
                                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                                    <circle cx="8.5" cy="8.5" r="1.5"/>
                                    <path d="M21 15l-5-5L5 21"/>
                                </svg>
                                <span>Ajouter une photo</span>
                                <input type="file" accept="image/*" capture="environment" id="photoInput" hidden />
                            </button>
                        </div>
                        <div class="photo-count">
                            <span id="photoCountText">0 photo(s)</span>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>Notes</h3>
                        <textarea id="notes" rows="4" placeholder="Commentaires, difficultés rencontrées, matériel utilisé..."></textarea>
                    </div>

                    <div class="form-section">
                        <h3>Signature <span class="required">*</span></h3>
                        <div class="signature-container">
                            <canvas id="signatureCanvas" width="300" height="150"></canvas>
                            <button type="button" id="clearSignature" class="btn-secondary btn-small">Effacer</button>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="button" id="cancelReport" class="btn-secondary">Annuler</button>
                        <button type="submit" id="submitReport" class="btn-primary">
                            <span>Soumettre le rapport</span>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="toast" class="toast"></div>
    <div id="loader" class="loader-overlay">
        <div class="loader-spinner"></div>
    </div>

    <script src="js/app.js"></script>
    <script src="js/auth.js"></script>
    <script src="js/reports.js"></script>
    <script src="js/signature.js"></script>
</body>
</html>
