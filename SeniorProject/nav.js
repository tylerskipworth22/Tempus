// nav.js
import { auth } from "./firebase.js"; // Import Firebase auth from your firebase.js

document.addEventListener("DOMContentLoaded", () => {
  const navLinks = document.querySelector(".nav-links");
  if (!navLinks) return;

  // Listen for login state changes
  auth.onAuthStateChanged(user => {
    // Clear existing links
    navLinks.innerHTML = "";

    if (user) {
      // User is logged in — show "Account" and "Log Out"
      const accountLi = document.createElement("li");
      const accountA = document.createElement("a");
      accountA.textContent = "Account";
      accountA.href = "account.php";
      accountLi.appendChild(accountA);

      const logoutLi = document.createElement("li");
      const logoutA = document.createElement("a");
      logoutA.textContent = "Log Out";
      logoutA.href = "#";
      logoutA.addEventListener("click", async () => {
        await auth.signOut();
        location.reload(); // Refresh page to update navbar
      });
      logoutLi.appendChild(logoutA);

      navLinks.appendChild(accountLi);
      navLinks.appendChild(logoutLi);
    } else {
      // User is not logged in — show "Login"
      const loginLi = document.createElement("li");
      const loginA = document.createElement("a");
      loginA.textContent = "Login";
      loginA.href = "login.html";
      loginLi.appendChild(loginA);

      const signupLi = document.createElement("li");
      const signupA = document.createElement("a");
      signupA.textContent = "Sign Up";
      signupA.href = "signup.html";
      signupLi.appendChild(signupA);

      navLinks.appendChild(loginLi);
      navLinks.appendChild(signupLi);
    }
  });
});
