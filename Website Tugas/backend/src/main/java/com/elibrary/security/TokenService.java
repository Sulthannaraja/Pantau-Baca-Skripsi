package com.elibrary.security;

import com.elibrary.model.User;
import com.elibrary.repository.UserRepository;
import lombok.RequiredArgsConstructor;
import org.springframework.stereotype.Service;

import javax.crypto.Mac;
import javax.crypto.spec.SecretKeySpec;
import java.nio.charset.StandardCharsets;
import java.time.Instant;
import java.util.Base64;
import java.util.Optional;

@Service
@RequiredArgsConstructor
public class TokenService {
    private static final String SECRET = "elibrary-super-secret-key";
    private static final long EXPIRE_SECONDS = 60L * 60L * 24L;
    private final UserRepository userRepository;

    public String issueToken(User user) {
        long exp = Instant.now().getEpochSecond() + EXPIRE_SECONDS;
        String payload = user.getId() + ":" + exp;
        String signature = sign(payload);
        String raw = payload + ":" + signature;
        return Base64.getUrlEncoder().withoutPadding().encodeToString(raw.getBytes(StandardCharsets.UTF_8));
    }

    public Optional<User> resolve(String token) {
        try {
            String decoded = new String(Base64.getUrlDecoder().decode(token), StandardCharsets.UTF_8);
            String[] parts = decoded.split(":");
            if (parts.length != 3) return Optional.empty();

            String payload = parts[0] + ":" + parts[1];
            String givenSig = parts[2];
            if (!sign(payload).equals(givenSig)) return Optional.empty();

            long userId = Long.parseLong(parts[0]);
            long exp = Long.parseLong(parts[1]);
            if (Instant.now().getEpochSecond() > exp) return Optional.empty();

            return userRepository.findById(userId);
        } catch (Exception ex) {
            return Optional.empty();
        }
    }

    private String sign(String payload) {
        try {
            Mac mac = Mac.getInstance("HmacSHA256");
            mac.init(new SecretKeySpec(SECRET.getBytes(StandardCharsets.UTF_8), "HmacSHA256"));
            byte[] sig = mac.doFinal(payload.getBytes(StandardCharsets.UTF_8));
            return Base64.getUrlEncoder().withoutPadding().encodeToString(sig);
        } catch (Exception e) {
            throw new RuntimeException("Gagal membuat token", e);
        }
    }
}
