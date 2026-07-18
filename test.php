<?php
header('Content-Type: application/json; charset=utf-8');
function getFilesAsJson(string $folderPath): string
{
    if (!is_dir($folderPath)) {
        return json_encode(['error' => 'Invalid folder path']);
    }

    $files = [];
    $items = scandir($folderPath);

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $fullPath = rtrim($folderPath, '/') . '/' . $item;

        $files[] = [
            'name'    => $item,
            'type'    => is_file($fullPath) ? 'file' : 'folder',
            'size'    => is_file($fullPath) ? filesize($fullPath) : 0,
            'created' => date('Y-m-d H:i:s', filectime($fullPath)),
            'modified'=> date('Y-m-d H:i:s', filemtime($fullPath)),
        ];
    }

    return json_encode($files, JSON_PRETTY_PRINT);
}

// Example usage:
echo getFilesAsJson(__DIR__);






