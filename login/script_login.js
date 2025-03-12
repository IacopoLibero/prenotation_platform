document.addEventListener('DOMContentLoaded', function() {
  // Get container and buttons/links
  const container = document.getElementById("container");
  const registerBtn = document.getElementById("register");
  const loginBtn = document.getElementById("login");
  const toSigninLinks = document.querySelectorAll('.to-signin');
  const toSignupLinks = document.querySelectorAll('.to-signup');
  
  // Desktop button event listeners
  if (registerBtn) {
    registerBtn.addEventListener("click", () => {
      container.classList.add("active");
    });
  }
  
  if (loginBtn) {
    loginBtn.addEventListener("click", () => {
      container.classList.remove("active");
    });
  }
  
  // Mobile link event listeners
  toSigninLinks.forEach(link => {
    link.addEventListener('click', function(e) {
      e.preventDefault(); // Prevent default link behavior
      container.classList.remove('active');
    });
  });
  
  toSignupLinks.forEach(link => {
    link.addEventListener('click', function(e) {
      e.preventDefault(); // Prevent default link behavior
      container.classList.add('active');
    });
  });
});
