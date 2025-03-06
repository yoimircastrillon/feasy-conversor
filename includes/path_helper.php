<?php
// includes/path_helper.php
// Funciones auxiliares para manejo de rutas dinámicas

/**
 * Determina el nivel del directorio actual con respecto al directorio raíz
 * @return string Prefijo relativo para rutas
 */
function getRelativePath() {
    // Obtener la ruta del script actual
    $currentPath = $_SERVER['SCRIPT_FILENAME'];
    $rootPath = ROOT_DIR;
    
    // Si ya estamos en el directorio raíz
    if (dirname($currentPath) === $rootPath) {
        return './';
    }
    
    // Contar cuántos niveles hay de diferencia
    $currentDirs = explode('/', str_replace('\\', '/', dirname($currentPath)));
    $rootDirs = explode('/', str_replace('\\', '/', $rootPath));
    
    // Encontrar cuántos segmentos comparten
    $i = 0;
    while ($i < count($rootDirs) && $i < count($currentDirs) && $rootDirs[$i] === $currentDirs[$i]) {
        $i++;
    }
    
    // Calcular cuántos niveles hay que subir
    $levelsUp = count($currentDirs) - $i;
    
    // Construir la ruta relativa
    return str_repeat('../', $levelsUp);
}

/**
 * Obtiene la ruta raíz relativa desde la ubicación del script actual
 * @return string Ruta relativa hacia la raíz del proyecto
 */
function getRootPath() {
    static $rootPath = null;
    
    if ($rootPath === null) {
        $rootPath = getRelativePath();
    }
    
    return $rootPath;
}

/**
 * Incluye un archivo usando la ruta raíz relativa
 * @param string $path Ruta del archivo relativa a la raíz
 * @return bool Éxito de la inclusión
 */
function includeFromRoot($path) {
    $fullPath = getRootPath() . ltrim($path, '/');
    if (file_exists($fullPath)) {
        include_once $fullPath;
        return true;
    }
    return false;
}

/**
 * Construye una URL para un asset usando la ruta relativa
 * @param string $path Ruta del asset relativa a la raíz
 * @return string URL del asset
 */
function assetUrl($path) {
    return getRootPath() . ltrim($path, '/');
}

/**
 * Construye una URL para una página usando rutas relativas
 * @param string $path Ruta de la página relativa a la raíz
 * @return string URL de la página
 */
function pageUrl($path) {
    return getRootPath() . ltrim($path, '/');
}

/**
 * Obtiene la ubicación del script actual dentro del proyecto
 * @return string Ruta relativa del script desde la raíz
 */
function getCurrentPagePath() {
    $scriptPath = $_SERVER['SCRIPT_FILENAME'];
    $rootDir = ROOT_DIR;
    
    if (strpos($scriptPath, $rootDir) === 0) {
        return substr($scriptPath, strlen($rootDir) + 1);
    }
    
    return basename($scriptPath);
}

/**
 * Verifica si la página actual coincide con la ruta especificada
 * @param string $path Ruta a verificar
 * @return bool Si la página actual coincide con la ruta
 */
function isCurrentPage($path) {
    $currentPage = getCurrentPagePath();
    return $currentPage === ltrim($path, '/');
}

/**
 * Determina si la URL actual está en una sección específica
 * @param string $section Sección a verificar (admin, user, etc.)
 * @return bool Si la URL actual está en la sección especificada
 */
function isSection($section) {
    $currentPage = getCurrentPagePath();
    return strpos($currentPage, $section . '/') === 0;
}