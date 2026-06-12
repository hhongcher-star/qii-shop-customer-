-- Remove the accidental test text already published by the visual editor.
UPDATE content_settings
SET setting_value = '🎀 选择规格'
WHERE setting_key = 'variant_choose_title'
  AND (
    TRIM(setting_value) = 'testing'
    OR TRIM(setting_value) = '选择规格testing'
    OR TRIM(setting_value) = '🎀 选择规格testing'
  );
