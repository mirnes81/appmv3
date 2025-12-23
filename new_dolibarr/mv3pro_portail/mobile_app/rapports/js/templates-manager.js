class TemplatesManager {
  constructor() {
    this.templates = {
      sdb_standard: {
        name: '🛁 Pose standard SDB',
        zone_travail: 'Salle de bain',
        surface_carrelee: 12,
        format_carreaux: '30×60',
        type_pose: 'Droite',
        zone_pose: 'Sol + Mur',
        travaux_realises: 'Pose carrelage salle de bain complète'
      },
      douche_complete: {
        name: '🚿 Douche complète',
        zone_travail: 'Douche',
        surface_carrelee: 8,
        format_carreaux: '30×60',
        type_pose: 'Droite',
        zone_pose: 'Mur',
        travaux_realises: 'Pose carrelage douche italienne'
      },
      cuisine_sol: {
        name: '🍳 Cuisine sol',
        zone_travail: 'Cuisine',
        surface_carrelee: 15,
        format_carreaux: '60×60',
        type_pose: 'Droite',
        zone_pose: 'Sol',
        travaux_realises: 'Pose carrelage sol cuisine'
      },
      cuisine_credence: {
        name: '🍳 Crédence cuisine',
        zone_travail: 'Cuisine',
        surface_carrelee: 4,
        format_carreaux: '30×60',
        type_pose: 'Droite',
        zone_pose: 'Mur',
        travaux_realises: 'Pose carrelage crédence cuisine'
      },
      salon_grand: {
        name: '🛋️ Grand salon',
        zone_travail: 'Salon',
        surface_carrelee: 40,
        format_carreaux: '60×60',
        type_pose: 'Droite',
        zone_pose: 'Sol',
        travaux_realises: 'Pose carrelage grand salon'
      },
      terrasse: {
        name: '🌴 Terrasse extérieure',
        zone_travail: 'Terrasse',
        surface_carrelee: 25,
        format_carreaux: '60×60',
        type_pose: 'Droite',
        zone_pose: 'Sol',
        travaux_realises: 'Pose carrelage terrasse extérieure'
      },
      wc_simple: {
        name: '🚽 WC simple',
        zone_travail: 'WC',
        surface_carrelee: 3,
        format_carreaux: '30×30',
        type_pose: 'Droite',
        zone_pose: 'Sol',
        travaux_realises: 'Pose carrelage WC'
      },
      couloir: {
        name: '🚪 Couloir',
        zone_travail: 'Couloir',
        surface_carrelee: 10,
        format_carreaux: '30×60',
        type_pose: 'Droite',
        zone_pose: 'Sol',
        travaux_realises: 'Pose carrelage couloir'
      },
      escalier: {
        name: '🪜 Escalier',
        zone_travail: 'Escalier',
        surface_carrelee: 12,
        format_carreaux: '30×60',
        type_pose: 'Droite',
        zone_pose: 'Escalier',
        travaux_realises: 'Habillage carrelage escalier complet'
      }
    };
  }

  getAll() {
    return this.templates;
  }

  getTemplate(key) {
    return this.templates[key] || null;
  }

  applyTemplate(templateKey, formElement) {
    const template = this.getTemplate(templateKey);
    if (!template) return;

    if (formElement.querySelector('[name="zone_travail"]')) {
      formElement.querySelector('[name="zone_travail"]').value = template.zone_travail;
    }

    const surfaceInput = formElement.querySelector('[name="surface_carrelee"]');
    if (surfaceInput) {
      surfaceInput.value = template.surface_carrelee;
    }

    const formatInput = formElement.querySelector('[name="format_carreaux"]');
    if (formatInput) {
      formatInput.value = template.format_carreaux;
    }

    const typePoseSelect = formElement.querySelector('[name="type_pose"]');
    if (typePoseSelect) {
      typePoseSelect.value = template.type_pose;
    }

    const zonePoseSelect = formElement.querySelector('[name="zone_pose"]');
    if (zonePoseSelect) {
      zonePoseSelect.value = template.zone_pose;
    }

    const travauxTextarea = formElement.querySelector('[name="travaux_realises"]');
    if (travauxTextarea) {
      travauxTextarea.value = template.travaux_realises;
    }

    const zoneButtons = formElement.querySelectorAll('.zone-btn');
    zoneButtons.forEach(btn => {
      if (btn.getAttribute('data-zone') === template.zone_travail) {
        btn.click();
      }
    });

    this.showNotification(`Template "${template.name}" appliqué`, 'success');
  }

  showNotification(message, type = 'success') {
    const colors = {
      success: '#10b981',
      info: '#0891b2'
    };

    const notification = document.createElement('div');
    notification.style.cssText = `
      position: fixed;
      top: 70px;
      left: 50%;
      transform: translateX(-50%);
      background: ${colors[type]};
      color: white;
      padding: 12px 24px;
      border-radius: 8px;
      font-weight: 600;
      z-index: 10000;
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    `;
    notification.textContent = message;
    document.body.appendChild(notification);

    setTimeout(() => notification.remove(), 2000);
  }
}

window.templatesManager = new TemplatesManager();
