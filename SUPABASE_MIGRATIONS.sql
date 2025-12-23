/*
  # MV3 Pro PWA - Schema Database Supabase

  ## À EXÉCUTER DANS VOTRE DASHBOARD SUPABASE

  Allez dans : Dashboard > SQL Editor > New Query
  Collez ce code SQL complet et exécutez-le

  ## Tables Créées

  ### 1. mobile_users - Utilisateurs mobiles
  ### 2. report_drafts - Brouillons de rapports (auto-save)
  ### 3. report_templates - Templates de rapports
  ### 4. sync_queue - File de synchronisation intelligente
  ### 5. offline_cache - Cache hors-ligne
  ### 6. photo_backups - Backup des photos
  ### 7. voice_notes - Notes vocales transcrites

  ## Sécurité
  - RLS activé sur toutes les tables
  - Policies restrictives par utilisateur
*/

-- Table des utilisateurs mobiles
CREATE TABLE IF NOT EXISTS mobile_users (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  dolibarr_user_id integer UNIQUE NOT NULL,
  email text UNIQUE NOT NULL,
  phone text,
  biometric_enabled boolean DEFAULT false,
  preferences jsonb DEFAULT '{"theme": "auto", "notifications": true, "autoSave": true}'::jsonb,
  last_sync timestamptz DEFAULT now(),
  created_at timestamptz DEFAULT now()
);

-- Table des brouillons de rapports
CREATE TABLE IF NOT EXISTS report_drafts (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id uuid NOT NULL REFERENCES mobile_users(id) ON DELETE CASCADE,
  draft_name text NOT NULL,
  report_type text NOT NULL,
  content jsonb NOT NULL,
  photos jsonb DEFAULT '[]'::jsonb,
  voice_notes jsonb DEFAULT '[]'::jsonb,
  gps_location jsonb,
  auto_saved_at timestamptz DEFAULT now(),
  created_at timestamptz DEFAULT now(),
  updated_at timestamptz DEFAULT now()
);

-- Table des templates de rapports
CREATE TABLE IF NOT EXISTS report_templates (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id uuid REFERENCES mobile_users(id) ON DELETE CASCADE,
  name text NOT NULL,
  description text,
  report_type text NOT NULL,
  template_data jsonb NOT NULL,
  is_public boolean DEFAULT false,
  usage_count integer DEFAULT 0,
  created_at timestamptz DEFAULT now(),
  updated_at timestamptz DEFAULT now()
);

-- Table de la file de synchronisation
CREATE TABLE IF NOT EXISTS sync_queue (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id uuid NOT NULL REFERENCES mobile_users(id) ON DELETE CASCADE,
  action_type text NOT NULL,
  priority integer DEFAULT 5,
  payload jsonb NOT NULL,
  status text DEFAULT 'pending',
  retry_count integer DEFAULT 0,
  error_message text,
  created_at timestamptz DEFAULT now(),
  synced_at timestamptz
);

-- Table du cache offline
CREATE TABLE IF NOT EXISTS offline_cache (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id uuid NOT NULL REFERENCES mobile_users(id) ON DELETE CASCADE,
  cache_key text NOT NULL,
  cache_type text NOT NULL,
  data jsonb NOT NULL,
  ttl integer DEFAULT 3600,
  expires_at timestamptz,
  created_at timestamptz DEFAULT now(),
  updated_at timestamptz DEFAULT now(),
  UNIQUE(user_id, cache_key)
);

-- Table des backups de photos
CREATE TABLE IF NOT EXISTS photo_backups (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id uuid NOT NULL REFERENCES mobile_users(id) ON DELETE CASCADE,
  original_name text NOT NULL,
  file_path text NOT NULL,
  file_size integer,
  mime_type text,
  width integer,
  height integer,
  compressed boolean DEFAULT false,
  related_to_type text,
  related_to_id text,
  gps_location jsonb,
  uploaded_at timestamptz DEFAULT now(),
  created_at timestamptz DEFAULT now()
);

-- Table des notes vocales
CREATE TABLE IF NOT EXISTS voice_notes (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id uuid NOT NULL REFERENCES mobile_users(id) ON DELETE CASCADE,
  transcription text,
  audio_duration integer,
  language text DEFAULT 'fr-FR',
  confidence_score numeric(3,2),
  related_to_type text,
  related_to_id uuid,
  created_at timestamptz DEFAULT now()
);

-- Indexes pour performance
CREATE INDEX IF NOT EXISTS idx_report_drafts_user ON report_drafts(user_id);
CREATE INDEX IF NOT EXISTS idx_report_drafts_type ON report_drafts(report_type);
CREATE INDEX IF NOT EXISTS idx_sync_queue_user_status ON sync_queue(user_id, status);
CREATE INDEX IF NOT EXISTS idx_sync_queue_priority ON sync_queue(priority, created_at);
CREATE INDEX IF NOT EXISTS idx_offline_cache_user_type ON offline_cache(user_id, cache_type);
CREATE INDEX IF NOT EXISTS idx_offline_cache_expires ON offline_cache(expires_at);
CREATE INDEX IF NOT EXISTS idx_photo_backups_user ON photo_backups(user_id);

-- Enable RLS
ALTER TABLE mobile_users ENABLE ROW LEVEL SECURITY;
ALTER TABLE report_drafts ENABLE ROW LEVEL SECURITY;
ALTER TABLE report_templates ENABLE ROW LEVEL SECURITY;
ALTER TABLE sync_queue ENABLE ROW LEVEL SECURITY;
ALTER TABLE offline_cache ENABLE ROW LEVEL SECURITY;
ALTER TABLE photo_backups ENABLE ROW LEVEL SECURITY;
ALTER TABLE voice_notes ENABLE ROW LEVEL SECURITY;

-- Policies pour mobile_users
CREATE POLICY "Users can view own profile"
  ON mobile_users FOR SELECT
  TO authenticated
  USING (auth.uid() = id);

CREATE POLICY "Users can update own profile"
  ON mobile_users FOR UPDATE
  TO authenticated
  USING (auth.uid() = id)
  WITH CHECK (auth.uid() = id);

-- Policies pour report_drafts
CREATE POLICY "Users can view own drafts"
  ON report_drafts FOR SELECT
  TO authenticated
  USING (user_id = auth.uid());

CREATE POLICY "Users can create own drafts"
  ON report_drafts FOR INSERT
  TO authenticated
  WITH CHECK (user_id = auth.uid());

CREATE POLICY "Users can update own drafts"
  ON report_drafts FOR UPDATE
  TO authenticated
  USING (user_id = auth.uid())
  WITH CHECK (user_id = auth.uid());

CREATE POLICY "Users can delete own drafts"
  ON report_drafts FOR DELETE
  TO authenticated
  USING (user_id = auth.uid());

-- Policies pour report_templates
CREATE POLICY "Users can view own and public templates"
  ON report_templates FOR SELECT
  TO authenticated
  USING (user_id = auth.uid() OR is_public = true);

CREATE POLICY "Users can create own templates"
  ON report_templates FOR INSERT
  TO authenticated
  WITH CHECK (user_id = auth.uid());

CREATE POLICY "Users can update own templates"
  ON report_templates FOR UPDATE
  TO authenticated
  USING (user_id = auth.uid())
  WITH CHECK (user_id = auth.uid());

CREATE POLICY "Users can delete own templates"
  ON report_templates FOR DELETE
  TO authenticated
  USING (user_id = auth.uid());

-- Policies pour sync_queue
CREATE POLICY "Users can view own sync queue"
  ON sync_queue FOR SELECT
  TO authenticated
  USING (user_id = auth.uid());

CREATE POLICY "Users can create own sync items"
  ON sync_queue FOR INSERT
  TO authenticated
  WITH CHECK (user_id = auth.uid());

CREATE POLICY "Users can update own sync items"
  ON sync_queue FOR UPDATE
  TO authenticated
  USING (user_id = auth.uid())
  WITH CHECK (user_id = auth.uid());

-- Policies pour offline_cache
CREATE POLICY "Users can view own cache"
  ON offline_cache FOR SELECT
  TO authenticated
  USING (user_id = auth.uid());

CREATE POLICY "Users can create own cache"
  ON offline_cache FOR INSERT
  TO authenticated
  WITH CHECK (user_id = auth.uid());

CREATE POLICY "Users can update own cache"
  ON offline_cache FOR UPDATE
  TO authenticated
  USING (user_id = auth.uid())
  WITH CHECK (user_id = auth.uid());

CREATE POLICY "Users can delete own cache"
  ON offline_cache FOR DELETE
  TO authenticated
  USING (user_id = auth.uid());

-- Policies pour photo_backups
CREATE POLICY "Users can view own photos"
  ON photo_backups FOR SELECT
  TO authenticated
  USING (user_id = auth.uid());

CREATE POLICY "Users can create own photos"
  ON photo_backups FOR INSERT
  TO authenticated
  WITH CHECK (user_id = auth.uid());

CREATE POLICY "Users can delete own photos"
  ON photo_backups FOR DELETE
  TO authenticated
  USING (user_id = auth.uid());

-- Policies pour voice_notes
CREATE POLICY "Users can view own voice notes"
  ON voice_notes FOR SELECT
  TO authenticated
  USING (user_id = auth.uid());

CREATE POLICY "Users can create own voice notes"
  ON voice_notes FOR INSERT
  TO authenticated
  WITH CHECK (user_id = auth.uid());

CREATE POLICY "Users can delete own voice notes"
  ON voice_notes FOR DELETE
  TO authenticated
  USING (user_id = auth.uid());

-- Fonction pour nettoyer le cache expiré
CREATE OR REPLACE FUNCTION clean_expired_cache()
RETURNS void AS $$
BEGIN
  DELETE FROM offline_cache WHERE expires_at < now();
END;
$$ LANGUAGE plpgsql;

-- Fonction pour mettre à jour expires_at automatiquement
CREATE OR REPLACE FUNCTION update_cache_expiry()
RETURNS TRIGGER AS $$
BEGIN
  NEW.expires_at = now() + (NEW.ttl || ' seconds')::interval;
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER set_cache_expiry
  BEFORE INSERT OR UPDATE ON offline_cache
  FOR EACH ROW
  EXECUTE FUNCTION update_cache_expiry();

-- Fonction pour mettre à jour updated_at
CREATE OR REPLACE FUNCTION update_updated_at()
RETURNS TRIGGER AS $$
BEGIN
  NEW.updated_at = now();
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER update_report_drafts_updated_at
  BEFORE UPDATE ON report_drafts
  FOR EACH ROW
  EXECUTE FUNCTION update_updated_at();

CREATE TRIGGER update_report_templates_updated_at
  BEFORE UPDATE ON report_templates
  FOR EACH ROW
  EXECUTE FUNCTION update_updated_at();
