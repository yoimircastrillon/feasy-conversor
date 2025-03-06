<?php
// includes/footer.php
// Este archivo contiene el pie de página compartido para todas las páginas del sistema

// Asegurarse de que las variables importantes están definidas
$currentYear = date('Y');
$appName = defined('APP_NAME') ? APP_NAME : 'FEASY';
$appVersion = defined('APP_VERSION') ? APP_VERSION : '1.0';
?>
            </div><!-- Fin del .main-content -->
        </div><!-- Fin del .layout -->
        
        <footer>
            <div class="footer-content">
                <div class="footer-logo">
                    <img src="https://esfeasy.com/web/image/website/1/logo/esfeasy?unique=c4e2bb3" alt="Feasy Logo" height="24">
                </div>
                <div class="footer-info">
                    &copy; <?php echo $currentYear; ?> FEASY SOFTWARE SOLUTIONS SAS - Todos los derechos reservados
                </div>
                <div class="footer-version">
                    <?php echo $appName; ?> v<?php echo $appVersion; ?>
                </div>
            </div>
        </footer>

        <!-- Scripts adicionales -->
        <?php if (isset($footerScripts) && is_array($footerScripts) && !empty($footerScripts)): ?>
            <?php foreach ($footerScripts as $script): ?>
                <script src="<?php echo (isset($basePath) ? $basePath : './') . $script; ?>"></script>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <script>
            // Script para cerrar los mensajes automáticamente
            document.addEventListener('DOMContentLoaded', function() {
                // Manejar mensajes
                const messages = document.querySelectorAll('.message');
                if (messages.length > 0) {
                    setTimeout(function() {
                        messages.forEach(function(message) {
                            message.style.opacity = '0';
                            message.style.transition = 'opacity 0.5s ease-out';
                            
                            setTimeout(function() {
                                message.style.display = 'none';
                            }, 500);
                        });
                    }, 5000);
                }
                
                // Activar tooltips
                const tooltips = document.querySelectorAll('[data-tooltip]');
                tooltips.forEach(function(tooltip) {
                    tooltip.addEventListener('mouseenter', function() {
                        const text = this.getAttribute('data-tooltip');
                        if (!text) return;
                        
                        const tooltipEl = document.createElement('div');
                        tooltipEl.className = 'tooltip';
                        tooltipEl.textContent = text;
                        document.body.appendChild(tooltipEl);
                        
                        const rect = this.getBoundingClientRect();
                        tooltipEl.style.top = rect.top + rect.height + 10 + 'px';
                        tooltipEl.style.left = rect.left + (rect.width / 2) - (tooltipEl.offsetWidth / 2) + 'px';
                        tooltipEl.style.opacity = '1';
                    });
                    
                    tooltip.addEventListener('mouseleave', function() {
                        const tooltip = document.querySelector('.tooltip');
                        if (tooltip) {
                            tooltip.style.opacity = '0';
                            setTimeout(function() {
                                tooltip.remove();
                            }, 300);
                        }
                    });
                });
            });
        </script>
        
        <style>
            /* Footer styles */
            footer {
                background-color: white;
                border-top: 1px solid var(--border-color);
                padding: 16px 24px;
                color: var(--gray-color);
                font-size: 13px;
                margin-top: auto;
            }
            
            .footer-content {
                display: flex;
                align-items: center;
                justify-content: space-between;
                max-width: 1200px;
                margin: 0 auto;
            }
            
            .footer-logo {
                display: flex;
                align-items: center;
            }
            
            .footer-info {
                text-align: center;
            }
            
            .footer-version {
                text-align: right;
                font-size: 12px;
            }
            
            /* Tooltip */
            .tooltip {
                position: fixed;
                background-color: #333;
                color: white;
                padding: 8px 12px;
                border-radius: 4px;
                font-size: 12px;
                z-index: 1000;
                opacity: 0;
                transition: opacity 0.3s;
                pointer-events: none;
                max-width: 250px;
                box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            }
            
            .tooltip:after {
                content: '';
                position: absolute;
                bottom: 100%;
                left: 50%;
                margin-left: -5px;
                border-width: 5px;
                border-style: solid;
                border-color: transparent transparent #333 transparent;
            }
            
            /* Responsive footer */
            @media (max-width: 768px) {
                .footer-content {
                    flex-direction: column;
                    gap: 8px;
                    text-align: center;
                }
                
                .footer-logo, .footer-info, .footer-version {
                    text-align: center;
                }
                
                .footer-logo {
                    justify-content: center;
                    margin-bottom: 8px;
                }
                
                .footer-version {
                    margin-top: 8px;
                }
            }
        </style>
        
        <?php if (isset($pageCustomScripts)): ?>
            <?php echo $pageCustomScripts; ?>
        <?php endif; ?>
    </body>
</html>