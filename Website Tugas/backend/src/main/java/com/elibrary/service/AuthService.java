package com.elibrary.service;

import com.elibrary.dto.AuthResponse;
import com.elibrary.dto.LoginRequest;
import com.elibrary.dto.RegisterRequest;
import com.elibrary.model.Role;
import com.elibrary.model.User;
import com.elibrary.repository.UserRepository;
import com.elibrary.security.TokenService;
import lombok.RequiredArgsConstructor;
import org.springframework.security.crypto.password.PasswordEncoder;
import org.springframework.stereotype.Service;

@Service
@RequiredArgsConstructor
public class AuthService {
    private final UserRepository userRepository;
    private final PasswordEncoder passwordEncoder;
    private final TokenService tokenService;

    public AuthResponse register(RegisterRequest request) {
        userRepository.findByEmail(request.email()).ifPresent(u -> {
            throw new IllegalArgumentException("Email sudah terdaftar");
        });

        Role role;
        try {
            role = Role.valueOf(request.role().toUpperCase());
        } catch (IllegalArgumentException ex) {
            throw new IllegalArgumentException("Role tidak valid");
        }
        if (role == Role.ADMIN) {
            throw new IllegalArgumentException("Registrasi admin tidak diizinkan");
        }

        User user = new User();
        user.setName(request.name());
        user.setEmail(request.email());
        user.setPassword(passwordEncoder.encode(request.password()));
        user.setRole(role);

        User saved = userRepository.save(user);
        String token = tokenService.issueToken(saved);

        return new AuthResponse(token, saved.getId(), saved.getName(), saved.getRole().name());
    }

    public AuthResponse login(LoginRequest request) {
        User user = userRepository.findByEmail(request.email())
                .or(() -> userRepository.findByName(request.email()))
                .orElseThrow(() -> new IllegalArgumentException("Email atau password salah"));

        if (!passwordEncoder.matches(request.password(), user.getPassword())) {
            throw new IllegalArgumentException("Email atau password salah");
        }

        String token = tokenService.issueToken(user);
        return new AuthResponse(token, user.getId(), user.getName(), user.getRole().name());
    }
}
