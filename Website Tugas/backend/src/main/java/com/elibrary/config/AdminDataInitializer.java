package com.elibrary.config;

import com.elibrary.model.Role;
import com.elibrary.model.User;
import com.elibrary.repository.UserRepository;
import lombok.RequiredArgsConstructor;
import org.springframework.boot.CommandLineRunner;
import org.springframework.security.crypto.password.PasswordEncoder;
import org.springframework.stereotype.Component;

@Component
@RequiredArgsConstructor
public class AdminDataInitializer implements CommandLineRunner {
    private final UserRepository userRepository;
    private final PasswordEncoder passwordEncoder;

    @Override
    public void run(String... args) {
        String adminEmail = "sulthantegarnaraja@admin.local";
        if (userRepository.findByEmail(adminEmail).isPresent()) {
            return;
        }

        User admin = new User();
        admin.setName("sulthantegarnaraja");
        admin.setEmail(adminEmail);
        admin.setPassword(passwordEncoder.encode("kanadabismillah"));
        admin.setRole(Role.ADMIN);
        userRepository.save(admin);
    }
}
