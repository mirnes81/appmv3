/**
 * API Rapports - Fonctions pour gérer les rapports chantier
 */

import { apiRequest } from './api';

export interface Project {
  id: number;
  ref: string;
  title: string;
  thirdparty_name?: string;
}

export interface ReportLine {
  id?: number;
  label: string;
  description?: string;
  qty_minutes?: number;
  note?: string;
  sort_order?: number;
}

export interface Report {
  id: number;
  ref: string;
  project?: {
    id: number;
    ref: string;
    title: string;
  } | null;
  author: {
    id: number;
    name: string;
    login: string;
  };
  date_report: number;
  time_start?: number | null;
  time_end?: number | null;
  duration_minutes?: number | null;
  note_public?: string;
  note_private?: string;
  status: number;
  status_label: string;
  lines?: ReportLine[];
  files?: ReportFile[];
  created_at: number;
  updated_at?: number;
}

export interface ReportFile {
  name: string;
  size: number;
  date: number;
  url: string;
}

export interface ReportListItem {
  id: number;
  ref: string;
  project_id?: number;
  project_ref?: string;
  project_title?: string;
  author_id: number;
  author_name: string;
  date_report: number;
  duration_minutes?: number;
  status: number;
  status_label: string;
  created_at: number;
}

/**
 * Récupérer la liste des projets
 */
export async function getProjects(search?: string): Promise<Project[]> {
  const params = search ? `?search=${encodeURIComponent(search)}` : '';
  const response = await apiRequest(`/reports_projects.php${params}`);
  return response.data;
}

/**
 * Récupérer la liste des rapports
 */
export async function getReports(filters?: {
  project_id?: number;
  date_from?: string;
  date_to?: string;
  status?: number;
  user_id?: number;
  limit?: number;
  offset?: number;
}): Promise<{ reports: ReportListItem[]; total: number }> {
  const params = new URLSearchParams();
  if (filters) {
    Object.entries(filters).forEach(([key, value]) => {
      if (value !== undefined && value !== null) {
        params.append(key, String(value));
      }
    });
  }

  const query = params.toString();
  const response = await apiRequest(`/reports_list.php${query ? '?' + query : ''}`);
  return response.data;
}

/**
 * Récupérer un rapport par ID
 */
export async function getReport(id: number): Promise<Report> {
  const response = await apiRequest(`/reports_get.php?id=${id}`);
  return response.data;
}

/**
 * Créer un nouveau rapport
 */
export async function createReport(data: {
  project_id?: number;
  date_report: string;
  time_start?: string;
  time_end?: string;
  duration_minutes?: number;
  note_public?: string;
  note_private?: string;
  status?: number;
  lines?: ReportLine[];
}): Promise<{ id: number; ref: string; status: number }> {
  const response = await apiRequest('/reports_create.php', {
    method: 'POST',
    body: JSON.stringify(data),
  });
  return response.data;
}

/**
 * Mettre à jour un rapport
 */
export async function updateReport(
  id: number,
  data: Partial<{
    project_id: number;
    date_report: string;
    time_start: string;
    time_end: string;
    duration_minutes: number;
    note_public: string;
    note_private: string;
  }>
): Promise<{ id: number; ref: string; status: number }> {
  const response = await apiRequest(`/reports_update.php?id=${id}`, {
    method: 'POST',
    body: JSON.stringify(data),
  });
  return response.data;
}

/**
 * Changer le statut d'un rapport
 */
export async function submitReport(
  id: number,
  status: number
): Promise<{ id: number; ref: string; status: number; status_label: string }> {
  const response = await apiRequest(`/reports_submit.php?id=${id}&status=${status}`, {
    method: 'POST',
  });
  return response.data;
}

/**
 * Supprimer un rapport (admin only)
 */
export async function deleteReport(id: number): Promise<void> {
  await apiRequest(`/reports_delete.php?id=${id}`, {
    method: 'POST',
  });
}

/**
 * Upload une photo pour un rapport
 */
export async function uploadReportPhoto(reportId: number, file: File): Promise<{ filename: string; url: string }> {
  const formData = new FormData();
  formData.append('file', file);

  const response = await apiRequest(`/reports_upload.php?report_id=${reportId}`, {
    method: 'POST',
    body: formData,
    headers: {}, // Let browser set Content-Type with boundary
  });

  return response.data;
}

/**
 * Supprimer une photo d'un rapport
 */
export async function deleteReportPhoto(reportId: number, filename: string): Promise<void> {
  await apiRequest(`/reports_delete_file.php?report_id=${reportId}&filename=${encodeURIComponent(filename)}`, {
    method: 'POST',
  });
}

/**
 * Constantes statuts
 */
export const REPORT_STATUS = {
  DRAFT: 0,
  SUBMITTED: 1,
  VALIDATED: 2,
  REJECTED: 9,
} as const;

export const REPORT_STATUS_LABELS = {
  [REPORT_STATUS.DRAFT]: 'Brouillon',
  [REPORT_STATUS.SUBMITTED]: 'Soumis',
  [REPORT_STATUS.VALIDATED]: 'Validé',
  [REPORT_STATUS.REJECTED]: 'Rejeté',
} as const;
