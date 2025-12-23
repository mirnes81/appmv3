-- Clés étrangères et contraintes supplémentaires
ALTER TABLE llx_mv3_rapport ADD CONSTRAINT fk_mv3_rapport_user FOREIGN KEY (fk_user) REFERENCES llx_user(rowid);
ALTER TABLE llx_mv3_rapport ADD CONSTRAINT fk_mv3_rapport_projet FOREIGN KEY (fk_projet) REFERENCES llx_projet(rowid) ON DELETE SET NULL;
ALTER TABLE llx_mv3_signalement ADD CONSTRAINT fk_mv3_signalement_user FOREIGN KEY (fk_user) REFERENCES llx_user(rowid);
ALTER TABLE llx_mv3_signalement ADD CONSTRAINT fk_mv3_signalement_projet FOREIGN KEY (fk_projet) REFERENCES llx_projet(rowid) ON DELETE SET NULL;
