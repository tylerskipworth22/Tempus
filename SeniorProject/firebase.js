// firebase.js
import { initializeApp } from "https://www.gstatic.com/firebasejs/12.4.0/firebase-app.js";
import { 
    getAuth,
    signInWithEmailAndPassword,
    onAuthStateChanged
} from "https://www.gstatic.com/firebasejs/12.4.0/firebase-auth.js";
import { getAnalytics } from "https://www.gstatic.com/firebasejs/12.4.0/firebase-analytics.js";

const firebaseConfig = {
  apiKey: "AIzaSyDopfhCwRQFT7VJGSvVatfyLl-oOWhy3eE",
  authDomain: "seniorproject-f13a3.firebaseapp.com",
  projectId: "seniorproject-f13a3",
  storageBucket: "seniorproject-f13a3.firebasestorage.app",
  messagingSenderId: "759770768294",
  appId: "1:759770768294:web:5b154637c9537595b3a567",
  measurementId: "G-WX8XVMQ0G6"
};

const app = initializeApp(firebaseConfig);
const auth = getAuth(app);
const analytics = getAnalytics(app);

export { auth, signInWithEmailAndPassword, onAuthStateChanged };
