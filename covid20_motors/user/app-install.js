let installPrompt = null;
const installButton = document.getElementById('installAppButton');
const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
const installStateKey = 'covid20AppInstalled';
let isInstalled = isStandalone;

try {
    isInstalled = isInstalled || localStorage.getItem(installStateKey) === 'true';
} catch (error) {
    console.warn('App installation state could not be loaded:', error);
}

if (isInstalled && installButton) installButton.hidden = true;

function markAppInstalled() {
    isInstalled = true;
    if (installButton) installButton.hidden = true;
    try {
        localStorage.setItem(installStateKey, 'true');
    } catch (error) {
        console.warn('App installation state could not be saved:', error);
    }
}

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('./service-worker.js').catch(error => {
            console.error('App offline support could not be enabled:', error);
        });
    });
}

window.addEventListener('beforeinstallprompt', event => {
    event.preventDefault();
    if (isInstalled) return;
    installPrompt = event;
    installButton?.classList.add('install-available');
    if (installButton) installButton.title = 'Install Co_vid 20\'s Motors';
});

window.addEventListener('appinstalled', () => {
    installPrompt = null;
    markAppInstalled();
});

installButton?.addEventListener('click', async () => {
    if (installPrompt) {
        installPrompt.prompt();
        const { outcome } = await installPrompt.userChoice;
        installPrompt = null;
        if (outcome === 'accepted') markAppInstalled();
        return;
    }

    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) ||
        (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    const instructions = isIOS
        ? 'Install Co_vid 20\'s Motors:\n1. Tap the Share button in Safari.\n2. Choose "Add to Home Screen."'
        : 'Install Co_vid 20\'s Motors:\nOpen your browser menu and choose "Install app" or "Add to Home screen."';
    window.alert(instructions);
});