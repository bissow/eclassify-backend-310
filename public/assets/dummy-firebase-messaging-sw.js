importScripts('https://www.gstatic.com/firebasejs/8.10.0/firebase-app.js');

importScripts('https://www.gstatic.com/firebasejs/8.10.0/firebase-messaging.js');

const firebaseConfig = {
    apiKey: apiKeyValue,
    authDomain: authDomainValue,
    projectId: projectIdValue,
    storageBucket: storageBucketValue,
    messagingSenderId: messagingSenderIdValue,
    appId: appIdValue,
    measurementId: measurementIdValue,
};

if (!firebase.apps.length) {
    firebase.initializeApp(firebaseConfig);
}

const messaging = firebase.messaging();

// Use BroadcastChannel to communicate with the main page
const broadcastChannel = new BroadcastChannel('firebase-messaging-channel');

// Handle background messages - for admin chat, we don't show notifications
// Instead, we send data to the main page via BroadcastChannel
messaging.setBackgroundMessageHandler(function (payload) {
   
    // Check if it's a chat notification (has item_offer_id)
    const data = payload.data || {};
    const itemOfferId = data.item_offer_id || payload.data?.item_offer_id || null;
    
    // Only handle chat notifications silently (no browser notification)
    if (itemOfferId) {
    
        // Send message to main page via BroadcastChannel
        broadcastChannel.postMessage({
            type: 'chat-message',
            itemOfferId: itemOfferId,
            data: data,
            timestamp: Date.now()
        });
        
        // Don't show browser notification - return resolved promise
        return Promise.resolve();
    }
    
    // For non-chat notifications, show browser notification (if needed)
    // But for admin chat, we skip this
    return Promise.resolve();
});

// Handle notification clicks (if any notifications are shown)
self.addEventListener('notificationclick', function (event) {
    event.notification.close();
    
    const clickAction = event.notification.data?.click_action;
    if (clickAction) {
        event.waitUntil(
            clients.openWindow(clickAction)
        );
    }
});
