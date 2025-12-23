class GPSManager {
  constructor() {
    this.currentPosition = null;
    this.watchId = null;
  }

  async getCurrentPosition() {
    return new Promise((resolve, reject) => {
      if (!navigator.geolocation) {
        reject(new Error('Géolocalisation non supportée'));
        return;
      }

      navigator.geolocation.getCurrentPosition(
        (position) => {
          this.currentPosition = {
            latitude: position.coords.latitude,
            longitude: position.coords.longitude,
            accuracy: position.coords.accuracy,
            timestamp: Date.now()
          };
          resolve(this.currentPosition);
        },
        (error) => {
          reject(this.getErrorMessage(error));
        },
        {
          enableHighAccuracy: true,
          timeout: 10000,
          maximumAge: 0
        }
      );
    });
  }

  startWatching(callback) {
    if (!navigator.geolocation) return;

    this.watchId = navigator.geolocation.watchPosition(
      (position) => {
        this.currentPosition = {
          latitude: position.coords.latitude,
          longitude: position.coords.longitude,
          accuracy: position.coords.accuracy,
          timestamp: Date.now()
        };
        callback(this.currentPosition);
      },
      (error) => console.error('GPS error:', error),
      {
        enableHighAccuracy: true,
        timeout: 5000,
        maximumAge: 0
      }
    );
  }

  stopWatching() {
    if (this.watchId !== null) {
      navigator.geolocation.clearWatch(this.watchId);
      this.watchId = null;
    }
  }

  getErrorMessage(error) {
    switch (error.code) {
      case error.PERMISSION_DENIED:
        return 'Permission GPS refusée';
      case error.POSITION_UNAVAILABLE:
        return 'Position GPS indisponible';
      case error.TIMEOUT:
        return 'Timeout GPS dépassé';
      default:
        return 'Erreur GPS inconnue';
    }
  }

  getGoogleMapsLink(lat, lon) {
    return `https://www.google.com/maps?q=${lat},${lon}`;
  }

  formatCoordinates(lat, lon, accuracy) {
    return {
      display: `${lat.toFixed(6)}, ${lon.toFixed(6)}`,
      accuracy: `±${Math.round(accuracy)}m`,
      link: this.getGoogleMapsLink(lat, lon)
    };
  }

  async captureLocation() {
    try {
      const position = await this.getCurrentPosition();
      const formatted = this.formatCoordinates(
        position.latitude,
        position.longitude,
        position.accuracy
      );

      return {
        ...position,
        ...formatted
      };
    } catch (error) {
      throw error;
    }
  }

  isLocationAccurate(accuracy) {
    return accuracy <= 50;
  }
}

window.gpsManager = new GPSManager();
