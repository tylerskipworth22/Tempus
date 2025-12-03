import { auth } from "./firebase.js";

document.addEventListener("DOMContentLoaded", () => {
  const navLinks = document.querySelector(".nav-links");
  if (!navLinks) return;

  auth.onAuthStateChanged(async (user) => {
    navLinks.innerHTML = "";

    if (user) {
      //user is logged in
      let accountHref = "account.php"; //default for normal users

      try {
        const res = await fetch("get_user_role.php", { credentials: "same-origin" });
        if (res.ok) {
          const data = await res.json();
          if (data.role === "moderator") accountHref = "modDash.php";
          else if (data.role === "admin") accountHref = "adminDash.php";
        } else {
          console.error("Failed to fetch role:", res.statusText);
        }
      } catch (err) {
        console.error("Error fetching role:", err);
      }

      //account link
      const accountLi = document.createElement("li");
      const accountA = document.createElement("a");
      accountA.textContent = "Account";
      accountA.href = accountHref;
      accountLi.appendChild(accountA);

      //log out link
      const logoutLi = document.createElement("li");
      const logoutA = document.createElement("a");
      logoutA.textContent = "Log Out";
      logoutA.href = "#";
      logoutA.addEventListener("click", async () => {
        try {
          await auth.signOut();
          location.reload();
        } catch (err) {
          console.error("Logout failed:", err);
        }
      });
      logoutLi.appendChild(logoutA);

      navLinks.appendChild(accountLi);
      navLinks.appendChild(logoutLi);

    } else {
      //not logged in
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
