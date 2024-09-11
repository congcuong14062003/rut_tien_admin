import { onMessage } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-messaging.js";
import { messaging } from './firebaseConfig.js'; // Import messaging từ file cấu hình Firebase

export function handleOnMessage(callback) {
  // Hàm này sẽ nhận callback để tùy chỉnh hành động khi nhận thông báo
  onMessage(messaging, (payload) => {
    console.log("Message received while online: ", payload);

    // Gọi hàm callback và truyền dữ liệu thông báo
    callback(payload);
  });
}
