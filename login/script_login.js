document.addEventListener('DOMContentLoaded', function() {
  // Get container and elements
  const container = document.getElementById("container");
  const registerBtn = document.getElementById("register");
  const loginBtn = document.getElementById("login");
  const toSigninLinks = document.querySelectorAll('.to-signin');
  const toSignupLinks = document.querySelectorAll('.to-signup');
  const signInForm = document.querySelector('.sign-in');
  const signUpForm = document.querySelector('.sign-up');
  
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
  
  // Enhanced mobile link event listeners with dramatic paper-lifting animation
  toSigninLinks.forEach(link => {
    link.addEventListener('click', function(e) {
      e.preventDefault(); // Prevent default link behavior
      
      // Don't do anything if already animating
      if (container.classList.contains('animating')) return;
      
      // Add classes to set up the animation
      container.classList.add('animating');
      signUpForm.classList.add('hiding');
      signInForm.classList.add('revealing');
      
      // Switch to sign-in after animation
      setTimeout(() => {
        container.classList.remove('active');
        
        // Clean up animation classes
        setTimeout(() => {
          container.classList.remove('animating');
          signUpForm.classList.remove('hiding');
          signInForm.classList.remove('revealing');
        }, 1500); // Increased animation duration for complete lift
      }, 50);
    });
  });
  
  toSignupLinks.forEach(link => {
    link.addEventListener('click', function(e) {
      e.preventDefault(); // Prevent default link behavior
      
      // Don't do anything if already animating
      if (container.classList.contains('animating')) return;
      
      // Add classes to set up the animation
      container.classList.add('animating');
      signInForm.classList.add('hiding');
      signUpForm.classList.add('revealing');
      
      // Switch to sign-up after animation
      setTimeout(() => {
        container.classList.add('active');
        
        // Clean up animation classes
        setTimeout(() => {
          container.classList.remove('animating');
          signInForm.classList.remove('hiding');
          signUpForm.classList.remove('revealing');
        }, 1500); // Increased animation duration for complete lift
      }, 50);
    });
  });
});
