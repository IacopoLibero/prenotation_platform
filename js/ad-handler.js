/**
 * Ad Handler for Programma Lezioni
 * Handles ad loading and gracefully manages errors
 */

(function() {
    // Safe ad loader function with error handling
    window.loadAd = function(containerId) {
        // Find the ad container
        const adContainer = document.getElementById(containerId);
        if (!adContainer) return;

        try {
            // Create a promise with timeout for ad loading
            const adPromise = new Promise((resolve, reject) => {
                // The ad script function with error handling
                const loadScript = function() {
                    try {
                        const e = document.createElement("script");
                        e.src = "//ad.altervista.org/js.ad/size=300X250/?ref=" + 
                               encodeURIComponent(location.hostname + location.pathname) + 
                               "&r=" + Date.now();
                        
                        // Set an onload handler
                        e.onload = function() { resolve(); };
                        
                        // Set an error handler
                        e.onerror = function() { 
                            reject();
                            // Add a fallback message
                            adContainer.innerHTML = '<div style="width:300px;height:250px;background:#f5f5f5;display:flex;justify-content:center;align-items:center;border:1px solid #ddd;font-family:Arial,sans-serif;color:#666;">Contenuto pubblicitario</div>';
                        };
                        
                        // Insert the script
                        const s = document.scripts;
                        const c = document.currentScript || s[s.length-1];
                        c.parentNode.insertBefore(e, c);
                    } catch (error) {
                        reject(error);
                    }
                };
                
                // Load the ad script
                loadScript();
                
                // Set a timeout in case the ad takes too long to load
                setTimeout(() => {
                    reject(new Error('Ad loading timeout'));
                }, 5000);
            });

            // Handle ad loading with proper error handling
            adPromise.catch(error => {
                console.log('Ad loading handled gracefully:', error);
                // Don't throw any errors to the browser console
            });
            
        } catch (error) {
            // Catch any other errors that might occur
            console.log('Ad error handled:', error);
        }
    };
})();