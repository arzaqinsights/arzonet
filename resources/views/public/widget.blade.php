(function() {
    // Prevent double loading
    if (window['ArzonetWidgetLoaded_' + '{{ $token }}']) return;
    window['ArzonetWidgetLoaded_' + '{{ $token }}'] = true;

    // Helper to get script attributes
    const script = document.currentScript || (() => {
        const scripts = document.getElementsByTagName('script');
        for (let s of scripts) {
            if (s.src && s.src.includes('{{ $token }}/widget.js')) {
                return s;
            }
        }
        return scripts[scripts.length - 1];
    })();

    const token = "{{ $token }}";
    const type = script.getAttribute('data-type') || 'popup'; // 'popup', 'slide-in', 'inline'
    const trigger = script.getAttribute('data-trigger') || 'delay'; // 'immediate', 'delay', 'scroll', 'exit-intent'
    const delay = parseInt(script.getAttribute('data-delay') || '3000', 10);
    const scrollPct = parseInt(script.getAttribute('data-scroll') || '50', 10);
    const formUrl = "{{ $formUrl }}";

    function initWidget() {
        if (type === 'inline') {
            renderInline();
        } else {
            setupTrigger();
        }
    }

    function renderInline() {
        const placeholderId = 'arzonet-form-' + token;
        let placeholder = document.getElementById(placeholderId);
        if (!placeholder) {
            // Fallback: search for a container with class or general placeholder
            placeholder = document.querySelector('[data-arzonet-form="' + token + '"]');
        }
        if (!placeholder) {
            console.warn('Arzonet Form: Placeholder element (#' + placeholderId + ') or [data-arzonet-form=\'' + token + '\'] not found for inline form.');
            return;
        }
        const iframe = createIframe();
        iframe.style.width = '100%';
        iframe.style.height = '480px';
        iframe.style.border = 'none';
        iframe.style.background = 'transparent';
        placeholder.appendChild(iframe);
    }

    function createIframe() {
        const iframe = document.createElement('iframe');
        iframe.src = formUrl + '?widget=1';
        iframe.style.border = 'none';
        iframe.style.width = '100%';
        iframe.style.height = '100%';
        iframe.setAttribute('frameborder', '0');
        iframe.setAttribute('scrolling', 'no');
        iframe.onload = function() {
            // Auto-adjust height when loaded if possible
            try {
                if (iframe.contentWindow.document.body) {
                    // Start height observer or set initial height
                    const resizeIframe = () => {
                        const h = iframe.contentWindow.document.body.scrollHeight;
                        if (h > 50) {
                            iframe.style.height = (h + 10) + 'px';
                        }
                    };
                    resizeIframe();
                    // Optional: poll/observe for multi-step height changes
                    setInterval(resizeIframe, 500);
                }
            } catch (e) {
                // Cross-origin fallback: keep height as default
            }
        };
        return iframe;
    }

    function setupTrigger() {
        if (trigger === 'immediate') {
            showModal();
        } else if (trigger === 'delay') {
            setTimeout(showModal, delay);
        } else if (trigger === 'scroll') {
            const handleScroll = () => {
                const totalHeight = document.documentElement.scrollHeight - window.innerHeight;
                if (totalHeight <= 0) return;
                const pct = (window.scrollY / totalHeight) * 100;
                if (pct >= scrollPct) {
                    showModal();
                    window.removeEventListener('scroll', handleScroll);
                }
            };
            window.addEventListener('scroll', handleScroll);
        } else if (trigger === 'exit-intent') {
            const handleMouseLeave = (e) => {
                if (e.clientY < 50) { // Mouse leaves top of page
                    showModal();
                    document.removeEventListener('mouseleave', handleMouseLeave);
                }
            };
            document.addEventListener('mouseleave', handleMouseLeave);
        }
    }

    function showModal() {
        // Prevent showing multiple times in same session if already closed
        if (sessionStorage.getItem('arzonet_closed_' + token)) return;

        // Overlay
        const overlay = document.createElement('div');
        overlay.id = 'arzonet-overlay-' + token;
        overlay.style.position = 'fixed';
        overlay.style.top = '0';
        overlay.style.left = '0';
        overlay.style.width = '100%';
        overlay.style.height = '100%';
        overlay.style.backgroundColor = type === 'slide-in' ? 'transparent' : 'rgba(15, 23, 42, 0.4)';
        overlay.style.backdropFilter = type === 'slide-in' ? 'none' : 'blur(4px)';
        overlay.style.webkitBackdropFilter = type === 'slide-in' ? 'none' : 'blur(4px)';
        overlay.style.zIndex = '999999';
        overlay.style.display = 'flex';
        overlay.style.alignItems = 'center';
        overlay.style.justifyContent = 'center';
        overlay.style.opacity = '0';
        overlay.style.transition = 'opacity 0.3s ease-in-out';
        if (type === 'slide-in') {
            overlay.style.pointerEvents = 'none';
        }

        // Modal container
        const container = document.createElement('div');
        container.style.position = 'relative';
        container.style.backgroundColor = '#fff';
        container.style.borderRadius = '6px';
        container.style.boxShadow = '0 25px 50px -12px rgba(0, 0, 0, 0.15), 0 0 1px 0 rgba(0, 0, 0, 0.1)';
        container.style.overflow = 'hidden';
        container.style.transition = 'transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.3s ease-in-out';
        
        if (type === 'slide-in') {
            container.style.position = 'fixed';
            container.style.bottom = '24px';
            container.style.right = '24px';
            container.style.width = '380px';
            container.style.height = '480px';
            container.style.maxWidth = 'calc(100vw - 48px)';
            container.style.maxHeight = 'calc(100vh - 48px)';
            container.style.pointerEvents = 'auto';
            container.style.transform = 'translateY(100px)';
        } else {
            // standard popup popup
            container.style.width = '90%';
            container.style.maxWidth = '420px';
            container.style.height = '500px';
            container.style.transform = 'scale(0.9)';
        }

        // Close button
        const closeBtn = document.createElement('button');
        closeBtn.innerHTML = '&times;';
        closeBtn.style.position = 'absolute';
        closeBtn.style.top = '10px';
        closeBtn.style.right = '15px';
        closeBtn.style.border = 'none';
        closeBtn.style.background = 'none';
        closeBtn.style.fontSize = '26px';
        closeBtn.style.cursor = 'pointer';
        closeBtn.style.color = '#9ca3af';
        closeBtn.style.zIndex = '999';
        closeBtn.style.lineHeight = '1';
        closeBtn.style.padding = '5px';
        closeBtn.style.transition = 'color 0.2s';
        closeBtn.onmouseenter = () => closeBtn.style.color = '#1f2937';
        closeBtn.onmouseleave = () => closeBtn.style.color = '#9ca3af';
        
        closeBtn.onclick = () => {
            if (type === 'slide-in') {
                container.style.transform = 'translateY(100px)';
            } else {
                container.style.transform = 'scale(0.9)';
            }
            overlay.style.opacity = '0';
            setTimeout(() => {
                document.body.removeChild(overlay);
            }, 300);
            sessionStorage.setItem('arzonet_closed_' + token, '1');
        };

        // Iframe
        const iframe = createIframe();
        
        container.appendChild(closeBtn);
        container.appendChild(iframe);
        overlay.appendChild(container);
        document.body.appendChild(overlay);

        // Animation trigger
        setTimeout(() => {
            overlay.style.opacity = '1';
            if (type === 'slide-in') {
                container.style.transform = 'translateY(0)';
            } else {
                container.style.transform = 'scale(1)';
            }
        }, 50);
    }

    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        initWidget();
    } else {
        document.addEventListener('DOMContentLoaded', initWidget);
    }
})();
