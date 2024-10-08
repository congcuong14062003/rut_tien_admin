import { initializeApp } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-app.js";
import { getMessaging, getToken } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-messaging.js";

// Cấu hình Firebase
const firebaseConfig = {
  apiKey: "AIzaSyCQwmleJnMG2zXkzA6QZ_Wq85efzdMNpag",
  authDomain: "push-notify-a24de.firebaseapp.com",
  projectId: "push-notify-a24de",
  storageBucket: "push-notify-a24de.appspot.com",
  messagingSenderId: "450727278972",
  appId: "1:450727278972:web:92444ae67390f148500cf9",
};

// Khởi tạo Firebase
const app = initializeApp(firebaseConfig);
const messaging = getMessaging(app);

// Hàm đăng ký Service Worker
export async function registerServiceWorker() {
  if ('serviceWorker' in navigator) {
    const registration = await navigator.serviceWorker.register('/sw.js');
    // Đảm bảo Service Worker đã sẵn sàng
    await navigator.serviceWorker.ready;
    return registration;
  } else {
    throw new Error("Service Worker is not supported.");
  }
}

// Hàm lấy token Firebase
export async function getTokenFirebase() {
  const permission = await Notification.requestPermission();
  if (permission !== "granted") {
    throw new Error("Permission not granted for notifications.");
  }

  const registration = await registerServiceWorker();

  // Lấy token Firebase
  const token = await getToken(messaging, {
    serviceWorkerRegistration: registration,
    vapidKey: "BOlnkd8sbjl-qJkW4YIxD1DBHwbSJOsofmwkCbcYQ7DxPrF9lkd6i9qz3IA_qaeIfKLkNoH2IGkBDdv68Wjl3nM",
  });

  if (!token) {
    throw new Error("Failed to retrieve token.");
  }

  return token;
}
