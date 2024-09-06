// Import Firebase scripts for Firebase Cloud Messaging
importScripts("https://www.gstatic.com/firebasejs/10.7.1/firebase-app-compat.js");
importScripts("https://www.gstatic.com/firebasejs/10.7.1/firebase-messaging-compat.js");

// Initialize the Firebase app in the service worker by passing the generated config
const firebaseConfig = {
    apiKey: "AIzaSyCQwmleJnMG2zXkzA6QZ_Wq85efzdMNpag",
    authDomain: "push-notify-a24de.firebaseapp.com",
    projectId: "push-notify-a24de",
    storageBucket: "push-notify-a24de.appspot.com",
    messagingSenderId: "450727278972",
    appId: "1:450727278972:web:92444ae67390f148500cf9",
};

firebase.initializeApp(firebaseConfig);

// Retrieve an instance of Firebase Messaging
const messaging = firebase.messaging();

// Handle background messages
messaging.onBackgroundMessage((payload) => {
    console.log("[sw.js] Received background message ", payload);

    // Customize notification here
    const notificationTitle = payload.notification.title || "Firebase Notification";
    const notificationOptions = {
        body: payload.notification.body || "You have a new message.",
        icon: "", // You can set a custom icon here
    };

    self.registration.showNotification(notificationTitle, notificationOptions);
});
