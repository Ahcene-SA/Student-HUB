<?php
// Affiche le chemin exact du php.ini actif
$iniPath = php_ini_loaded_file();
if ($iniPath) {
    echo "<h2>✅ Fichier php.ini actif :</h2>";
    echo "<code style='background:#f0f0f0;padding:8px;border-radius:4px;'>" . htmlspecialchars($iniPath) . "</code>";
} else {
    echo "<h2>❌ Aucun php.ini chargé</h2>";
}

echo "<hr><h3>Limites d'upload actuelles :</h3>";
echo "<ul>";
echo "<li><strong>upload_max_filesize :</strong> " . ini_get('upload_max_filesize') . "</li>";
echo "<li><strong>post_max_size :</strong> " . ini_get('post_max_size') . "</li>";
echo "<li><strong>max_file_uploads :</strong> " . ini_get('max_file_uploads') . "</li>";
echo "</ul>";

echo "<hr><p>Modifie <code>upload_max_filesize</code> et <code>post_max_size</code> dans le fichier ci-dessus, puis redémarre WAMP.</p>";
?>
