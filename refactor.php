<?php

$baseDir = __DIR__;

// Mappings for old class name to new class name
$classMap = [];

// 1. Models
$modelsDir = $baseDir . '/app/Models';
if (is_dir($modelsDir)) {
    foreach (scandir($modelsDir) as $file) {
        if ($file === '.' || $file === '..') continue;
        if (preg_match('/^([a-zA-Z0-9_]+)\.php$/', $file, $matches)) {
            $oldName = $matches[1];
            if (str_ends_with($oldName, 'M')) continue;
            $newName = $oldName . 'M';
            $classMap['App\\Models\\' . $oldName] = 'App\\Models\\' . $newName;
            $classMap[$oldName . '::'] = $newName . '::';
            // Rename file
            rename($modelsDir . '/' . $file, $modelsDir . '/' . $newName . '.php');
        }
    }
}

// 2. Services
$servicesDir = $baseDir . '/app/Services';
if (is_dir($servicesDir)) {
    foreach (scandir($servicesDir) as $file) {
        if ($file === '.' || $file === '..') continue;
        if (preg_match('/^([a-zA-Z0-9_]+)\.php$/', $file, $matches)) {
            $oldName = $matches[1];
            $newName = str_replace('Service', 'S', $oldName);
            if ($oldName === $newName) {
                $newName = $oldName . 'S';
            }
            $classMap['App\\Services\\' . $oldName] = 'App\\Services\\' . $newName;
            $classMap[$oldName . '::'] = $newName . '::';
            rename($servicesDir . '/' . $file, $servicesDir . '/' . $newName . '.php');
        }
    }
}

// 3. Controllers
function renameControllers($dir, &$classMap) {
    global $baseDir;
    foreach (scandir($dir) as $file) {
        if ($file === '.' || $file === '..') continue;
        $path = $dir . '/' . $file;
        if (is_dir($path)) {
            renameControllers($path, $classMap);
        } else if (preg_match('/^([a-zA-Z0-9_]+)\.php$/', $file, $matches)) {
            $oldName = $matches[1];
            if ($oldName === 'Controller') {
                $newName = 'ControllerC';
            } else {
                $newName = str_replace('Controller', 'C', $oldName);
                if ($oldName === $newName && !str_ends_with($oldName, 'C')) {
                    $newName = $oldName . 'C';
                }
            }
            
            $relativePath = str_replace($baseDir . '/app/Http/Controllers/', '', $dir);
            $namespace = 'App\\Http\\Controllers';
            if ($relativePath && $relativePath !== $dir) {
                $namespace .= '\\' . str_replace('/', '\\', $relativePath);
            }
            
            $classMap[$namespace . '\\' . $oldName] = $namespace . '\\' . $newName;
            $classMap[$oldName . '::'] = $newName . '::';
            rename($path, $dir . '/' . $newName . '.php');
        }
    }
}

$controllersDir = $baseDir . '/app/Http/Controllers';
if (is_dir($controllersDir)) {
    renameControllers($controllersDir, $classMap);
}

echo "Class mappings:\n";
print_r($classMap);

// 4. Update file contents recursively
function updateFileContents($dir, $classMap) {
    foreach (scandir($dir) as $file) {
        if ($file === '.' || $file === '..') continue;
        if ($file === 'vendor' || $file === 'node_modules' || $file === 'storage') continue;
        $path = $dir . '/' . $file;
        
        if (is_dir($path)) {
            updateFileContents($path, $classMap);
        } else if (preg_match('/\.(php)$/', $file)) {
            $content = file_get_contents($path);
            $newContent = $content;
            
            foreach ($classMap as $old => $new) {
                // Class definitions
                if (str_contains($old, '\\')) {
                    $oldClass = basename(str_replace('\\', '/', $old));
                    $newClass = basename(str_replace('\\', '/', $new));
                    $newContent = preg_replace('/class\s+' . $oldClass . '\b/', 'class ' . $newClass, $newContent);
                    $newContent = preg_replace('/extends\s+' . $oldClass . '\b/', 'extends ' . $newClass, $newContent);
                    
                    // use statements
                    $newContent = str_replace('use ' . $old . ';', 'use ' . $new . ';', $newContent);
                    
                    // strings (config files)
                    $newContent = str_replace(addslashes($old), addslashes($new), $newContent);
                    $newContent = str_replace($old, $new, $newContent);
                } else {
                    $newContent = str_replace($old, $new, $newContent);
                }
            }
            
            if ($content !== $newContent) {
                file_put_contents($path, $newContent);
                echo "Updated: $path\n";
            }
        }
    }
}

updateFileContents($baseDir . '/app', $classMap);
updateFileContents($baseDir . '/config', $classMap);
updateFileContents($baseDir . '/routes', $classMap);
updateFileContents($baseDir . '/bootstrap', $classMap);
updateFileContents($baseDir . '/database', $classMap);

echo "Refactor completed!\n";
