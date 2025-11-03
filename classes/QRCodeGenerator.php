<?php
    /**
     * Alternative QR Code Generator that doesn't require GD extension
     * Uses QR Server API as a fallback
     */
class QRCodeGenerator {
    
    /**
     * Generate QR code using QR Server API
     * @param string $data
     * @param string $filename
     * @param int $size
     * @return bool
     */
    public static function generateWithQRServer($data, $filename, $size = 200) {
        try {
            // Create directory if it doesn't exist
            $dir = dirname($filename);
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }
            
            // Use a simpler QR API that's more reliable
            $apiUrl = 'https://api.qrserver.com/v1/create-qr-code/?';
            $params = [
                'size' => $size . 'x' . $size,
                'data' => $data,
                'format' => 'png'
            ];
            
            $fullUrl = $apiUrl . http_build_query($params);
            
            // Set up context for file_get_contents
            $context = stream_context_create([
                'http' => [
                    'timeout' => 30,
                    'user_agent' => 'CLRMS/1.0'
                ]
            ]);
            
            // Download the QR code image
            $imageData = file_get_contents($fullUrl, false, $context);
            
            if ($imageData === false) {
                error_log('[QR Code Generation Error] Failed to download from QR Server API');
                return false;
            }
            
            // Save the image
            if (file_put_contents($filename, $imageData) === false) {
                error_log('[QR Code Generation Error] Failed to save QR code file: ' . $filename);
                return false;
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log('[QR Code Generation Error] ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generate QR code using phpqrcode (if GD is available) or QR Server API (fallback)
     * @param string $data
     * @param string $filename
     * @param int $size
     * @return bool
     */
    public static function generate($data, $filename, $size = 200) {
        // First try phpqrcode if GD is available
        if (extension_loaded('gd')) {
            try {
                require_once __DIR__ . '/../vendor/phpqrcode/qrlib.php';
                QRcode::png($data, $filename, QR_ECLEVEL_L, 5);
                
                if (file_exists($filename)) {
                    return true;
                }
            } catch (Exception $e) {
                error_log('[QR Code Generation Error] phpqrcode failed: ' . $e->getMessage());
            }
        }
        
        // Fallback to QR Server API
        return self::generateWithQRServer($data, $filename, $size);
    }
    
    /**
     * Check if GD extension is available
     * @return bool
     */
    public static function isGDAvailable() {
        return extension_loaded('gd');
    }
    
    /**
     * Get available QR generation methods
     * @return array
     */
    public static function getAvailableMethods() {
        $methods = [];
        
        if (extension_loaded('gd')) {
            $methods[] = 'phpqrcode (GD extension)';
        }
        
        $methods[] = 'QR Server API (fallback)';
        
        return $methods;
    }
}
?> 