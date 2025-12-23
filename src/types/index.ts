export interface User {
  id: string;
  dolibarr_user_id: number;
  email: string;
  name: string;
  phone?: string;
  biometric_enabled: boolean;
  preferences: UserPreferences;
  last_sync?: string;
}

export interface UserPreferences {
  theme: 'light' | 'dark' | 'auto';
  notifications: boolean;
  autoSave: boolean;
  cameraQuality: 'low' | 'medium' | 'high';
  voiceLanguage: string;
}

export interface Report {
  id: string;
  user_id: string;
  project_id?: number;
  client_name: string;
  date: string;
  start_time: string;
  end_time: string;
  description: string;
  observations?: string;
  materials_used?: Material[];
  photos: Photo[];
  voice_notes?: VoiceNote[];
  gps_location?: GPSLocation;
  weather?: WeatherData;
  status: 'draft' | 'pending' | 'synced';
  created_at: string;
  updated_at: string;
}

export interface Regie {
  id: string;
  user_id: string;
  date: string;
  client_name: string;
  project_name: string;
  workers: Worker[];
  hours_worked: number;
  description: string;
  materials?: Material[];
  photos: Photo[];
  signature?: string;
  status: 'draft' | 'pending' | 'synced';
  created_at: string;
}

export interface Worker {
  name: string;
  hours: number;
  rate?: number;
}

export interface SensPose {
  id: string;
  user_id: string;
  client_name: string;
  address: string;
  date: string;
  tiles: TileInfo[];
  joint_color?: string;
  joint_width?: string;
  observations?: string;
  photos: Photo[];
  signature?: string;
  status: 'draft' | 'pending' | 'synced';
  created_at: string;
}

export interface TileInfo {
  room: string;
  direction: 'vertical' | 'horizontal';
  starting_point: string;
  tile_size?: string;
  notes?: string;
}

export interface Material {
  id?: number;
  name: string;
  quantity: number;
  unit: string;
  notes?: string;
}

export interface Photo {
  id: string;
  filename: string;
  url: string;
  thumbnail?: string;
  size: number;
  width?: number;
  height?: number;
  gps_location?: GPSLocation;
  taken_at: string;
  uploaded: boolean;
}

export interface VoiceNote {
  id: string;
  transcription: string;
  duration: number;
  confidence: number;
  language: string;
  created_at: string;
}

export interface GPSLocation {
  latitude: number;
  longitude: number;
  accuracy: number;
  timestamp: string;
}

export interface WeatherData {
  temperature: number;
  conditions: string;
  icon: string;
  humidity: number;
  wind_speed: number;
}

export interface ReportTemplate {
  id: string;
  user_id?: string;
  name: string;
  description?: string;
  report_type: 'rapport' | 'regie' | 'sens_pose';
  template_data: Partial<Report | Regie | SensPose>;
  is_public: boolean;
  usage_count: number;
  created_at: string;
}

export interface SyncQueueItem {
  id: string;
  user_id: string;
  action_type: 'create_report' | 'create_regie' | 'create_sens_pose' | 'upload_photo' | 'update_data';
  priority: number;
  payload: any;
  status: 'pending' | 'syncing' | 'completed' | 'failed';
  retry_count: number;
  error_message?: string;
  created_at: string;
}

export interface CacheEntry {
  id: string;
  user_id: string;
  cache_key: string;
  cache_type: 'clients' | 'projects' | 'materials' | 'users';
  data: any;
  ttl: number;
  expires_at: string;
  created_at: string;
  updated_at: string;
}

export interface DashboardStats {
  reports_today: number;
  reports_week: number;
  reports_month: number;
  hours_today: number;
  hours_week: number;
  pending_sync: number;
  photos_count: number;
}

export interface NotificationItem {
  id: string;
  title: string;
  message: string;
  type: 'info' | 'success' | 'warning' | 'error';
  read: boolean;
  created_at: string;
}
