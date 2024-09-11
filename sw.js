importScripts("https://www.gstatic.com/firebasejs/10.7.1/firebase-app-compat.js");
importScripts("https://www.gstatic.com/firebasejs/10.7.1/firebase-messaging-compat.js");

// Cấu hình Firebase
const firebaseConfig = {
  apiKey: "AIzaSyCQwmleJnMG2zXkzA6QZ_Wq85efzdMNpag",
  authDomain: "push-notify-a24de.firebaseapp.com",
  projectId: "push-notify-a24de",
  storageBucket: "push-notify-a24de.appspot.com",
  messagingSenderId: "450727278972",
  appId: "1:450727278972:web:92444ae67390f148500cf9",
};

firebase.initializeApp(firebaseConfig);

// Lấy instance của Firebase Messaging
const messaging = firebase.messaging();

// Xử lý thông báo nền
messaging.onBackgroundMessage((payload) => {
  console.log("[sw.js] Received background message ", payload);

  const notificationTitle = payload.notification.title || "Firebase Notification";
  const notificationOptions = {
    body: payload.notification.body || "You have a new message.",
    icon: payload.notification.icon || "", // Có thể đặt icon tùy chỉnh tại đây
  };

  self.registration.showNotification(notificationTitle, notificationOptions);
});
