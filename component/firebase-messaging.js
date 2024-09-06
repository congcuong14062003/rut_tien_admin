import { getMessaging, onMessage } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-messaging-compat.js";

// Firebase config
const firebaseConfig = {
    apiKey: "AIzaSyCQwmleJnMG2zXkzA6QZ_Wq85efzdMNpag",
    authDomain: "push-notify-a24de.firebaseapp.com",
    projectId: "push-notify-a24de",
    storageBucket: "push-notify-a24de.appspot.com",
    messagingSenderId: "450727278972",
    appId: "1:450727278972:web:92444ae67390f148500cf9",
};

// Initialize Firebase
firebase.initializeApp(firebaseConfig);

// Initialize Firebase Messaging
const messaging = getMessaging();

// Lắng nghe tin nhắn foreground
onMessage(messaging, (payload) => {
    console.log("Received message in foreground: ", payload);

    if (Notification.permission === "granted") {
        const notificationTitle = payload.notification?.title || "You have a new message!";
        const notificationOptions = {
            body: payload.notification?.body || "Click to view the message.",
            icon: payload.notification?.icon || "/default-icon.png",
        };
        new Notification(notificationTitle, notificationOptions);
    }
});
