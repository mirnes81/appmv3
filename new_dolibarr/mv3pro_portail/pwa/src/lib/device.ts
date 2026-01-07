export interface GeoPosition {
  latitude: number;
  longitude: number;
  accuracy: number;
  timestamp: number;
}

export async function getGeolocation(): Promise<GeoPosition> {
  if (!navigator.geolocation) {
    throw new Error('Géolocalisation non supportée');
  }

  return new Promise((resolve, reject) => {
    navigator.geolocation.getCurrentPosition(
      (position) => {
        resolve({
          latitude: position.coords.latitude,
          longitude: position.coords.longitude,
          accuracy: position.coords.accuracy,
          timestamp: position.timestamp,
        });
      },
      (error) => {
        reject(new Error(error.message || 'Erreur géolocalisation'));
      },
      {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 0,
      }
    );
  });
}

export interface CameraOptions {
  maxWidth?: number;
  maxHeight?: number;
  quality?: number;
}

export async function capturePhoto(options: CameraOptions = {}): Promise<string> {
  const input = document.createElement('input');
  input.type = 'file';
  input.accept = 'image/*';
  input.capture = 'environment' as any;

  return new Promise((resolve, reject) => {
    input.onchange = async (e: any) => {
      const file = e.target?.files?.[0];
      if (!file) {
        reject(new Error('Aucune photo sélectionnée'));
        return;
      }

      try {
        const base64 = await fileToBase64(file, options);
        resolve(base64);
      } catch (err) {
        reject(err);
      }
    };

    input.oncancel = () => {
      reject(new Error('Capture annulée'));
    };

    input.click();
  });
}

async function fileToBase64(file: File, options: CameraOptions = {}): Promise<string> {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();

    reader.onload = (e) => {
      const img = new Image();
      img.onload = () => {
        const canvas = document.createElement('canvas');
        let width = img.width;
        let height = img.height;

        if (options.maxWidth && width > options.maxWidth) {
          height = (height * options.maxWidth) / width;
          width = options.maxWidth;
        }
        if (options.maxHeight && height > options.maxHeight) {
          width = (width * options.maxHeight) / height;
          height = options.maxHeight;
        }

        canvas.width = width;
        canvas.height = height;

        const ctx = canvas.getContext('2d');
        if (!ctx) {
          reject(new Error('Erreur canvas'));
          return;
        }

        ctx.drawImage(img, 0, 0, width, height);
        const base64 = canvas.toDataURL('image/jpeg', options.quality || 0.85);
        resolve(base64);
      };

      img.onerror = () => reject(new Error('Erreur chargement image'));
      img.src = e.target?.result as string;
    };

    reader.onerror = () => reject(new Error('Erreur lecture fichier'));
    reader.readAsDataURL(file);
  });
}

export interface SignatureOptions {
  width?: number;
  height?: number;
  strokeColor?: string;
  lineWidth?: number;
}

export class SignatureCapture {
  private canvas: HTMLCanvasElement;
  private ctx: CanvasRenderingContext2D;
  private isDrawing = false;
  private lastX = 0;
  private lastY = 0;

  constructor(canvas: HTMLCanvasElement, options: SignatureOptions = {}) {
    this.canvas = canvas;
    const ctx = canvas.getContext('2d');
    if (!ctx) throw new Error('Canvas non supporté');
    this.ctx = ctx;

    this.canvas.width = options.width || 300;
    this.canvas.height = options.height || 150;

    this.ctx.strokeStyle = options.strokeColor || '#000';
    this.ctx.lineWidth = options.lineWidth || 2;
    this.ctx.lineCap = 'round';
    this.ctx.lineJoin = 'round';

    this.setupEvents();
  }

  private setupEvents() {
    this.canvas.addEventListener('mousedown', this.startDrawing.bind(this));
    this.canvas.addEventListener('mousemove', this.draw.bind(this));
    this.canvas.addEventListener('mouseup', this.stopDrawing.bind(this));
    this.canvas.addEventListener('touchstart', this.handleTouchStart.bind(this));
    this.canvas.addEventListener('touchmove', this.handleTouchMove.bind(this));
    this.canvas.addEventListener('touchend', this.stopDrawing.bind(this));
  }

  private startDrawing(e: MouseEvent) {
    this.isDrawing = true;
    const rect = this.canvas.getBoundingClientRect();
    this.lastX = e.clientX - rect.left;
    this.lastY = e.clientY - rect.top;
  }

  private handleTouchStart(e: TouchEvent) {
    e.preventDefault();
    this.isDrawing = true;
    const rect = this.canvas.getBoundingClientRect();
    const touch = e.touches[0];
    this.lastX = touch.clientX - rect.left;
    this.lastY = touch.clientY - rect.top;
  }

  private draw(e: MouseEvent) {
    if (!this.isDrawing) return;
    e.preventDefault();

    const rect = this.canvas.getBoundingClientRect();
    const x = e.clientX - rect.left;
    const y = e.clientY - rect.top;

    this.ctx.beginPath();
    this.ctx.moveTo(this.lastX, this.lastY);
    this.ctx.lineTo(x, y);
    this.ctx.stroke();

    this.lastX = x;
    this.lastY = y;
  }

  private handleTouchMove(e: TouchEvent) {
    if (!this.isDrawing) return;
    e.preventDefault();

    const rect = this.canvas.getBoundingClientRect();
    const touch = e.touches[0];
    const x = touch.clientX - rect.left;
    const y = touch.clientY - rect.top;

    this.ctx.beginPath();
    this.ctx.moveTo(this.lastX, this.lastY);
    this.ctx.lineTo(x, y);
    this.ctx.stroke();

    this.lastX = x;
    this.lastY = y;
  }

  private stopDrawing() {
    this.isDrawing = false;
  }

  clear() {
    this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
  }

  toDataURL(): string {
    return this.canvas.toDataURL('image/png');
  }

  isEmpty(): boolean {
    const imageData = this.ctx.getImageData(0, 0, this.canvas.width, this.canvas.height);
    return !imageData.data.some((channel) => channel !== 0);
  }
}
