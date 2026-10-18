package com.elibrary.controller;

import com.elibrary.dto.UserResponse;
import com.elibrary.model.Role;
import com.elibrary.repository.UserRepository;
import com.elibrary.security.UserPrincipal;
import lombok.RequiredArgsConstructor;
import org.springframework.security.core.annotation.AuthenticationPrincipal;
import org.springframework.web.bind.annotation.*;

import java.util.List;

@RestController
@RequestMapping("/api/users")
@RequiredArgsConstructor
public class UserController {
    private final UserRepository userRepository;

    @GetMapping
    public List<UserResponse> list(@AuthenticationPrincipal UserPrincipal user) {
        requireAdmin(user);
        return userRepository.findAll().stream()
                .map(u -> new UserResponse(u.getId(), u.getName(), u.getEmail(), u.getRole().name()))
                .toList();
    }

    @DeleteMapping("/{id}")
    public void delete(@AuthenticationPrincipal UserPrincipal user, @PathVariable Long id) {
        requireAdmin(user);
        if (user.getId().equals(id)) {
            throw new IllegalArgumentException("Admin tidak bisa menghapus akun sendiri");
        }
        userRepository.deleteById(id);
    }

    private void requireAdmin(UserPrincipal user) {
        if (user == null || user.getRole() != Role.ADMIN) {
            throw new SecurityException("Hanya admin yang boleh mengakses");
        }
    }
}
