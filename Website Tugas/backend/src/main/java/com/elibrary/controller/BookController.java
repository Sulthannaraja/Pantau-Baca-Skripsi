package com.elibrary.controller;

import com.elibrary.dto.BookRequest;
import com.elibrary.dto.BookPageMetaResponse;
import com.elibrary.dto.BookResponse;
import com.elibrary.model.Book;
import com.elibrary.model.Role;
import com.elibrary.security.UserPrincipal;
import com.elibrary.service.BookService;
import jakarta.validation.Valid;
import lombok.RequiredArgsConstructor;
import org.springframework.core.io.Resource;
import org.springframework.http.ResponseEntity;
import org.springframework.security.core.annotation.AuthenticationPrincipal;
import org.springframework.web.bind.annotation.*;
import org.springframework.web.multipart.MultipartFile;

import java.util.List;

@RestController
@RequestMapping("/api/books")
@RequiredArgsConstructor
public class BookController {
    private final BookService bookService;

    @GetMapping
    public List<BookResponse> listBooks() {
        return bookService.getAll().stream()
                .map(this::toResponse)
                .toList();
    }

    @GetMapping("/{id}")
    public BookResponse getBook(@PathVariable Long id) {
        return toResponse(bookService.getById(id));
    }

    @PostMapping
    public BookResponse createBook(@AuthenticationPrincipal UserPrincipal user,
                                   @Valid @RequestBody BookRequest request) {
        requireAdmin(user);
        return toResponse(bookService.create(request));
    }

    @PostMapping("/upload")
    public BookResponse uploadBook(@AuthenticationPrincipal UserPrincipal user,
                                   @RequestParam String title,
                                   @RequestParam String author,
                                   @RequestParam(required = false) String category,
                                   @RequestParam(required = false) String cover,
                                   @RequestParam("file") MultipartFile file) {
        requireAdmin(user);
        if (!"application/pdf".equalsIgnoreCase(file.getContentType())) {
            throw new IllegalArgumentException("File harus PDF");
        }
        return toResponse(bookService.createWithUpload(title, author, category, cover, file));
    }

    @DeleteMapping("/{id}")
    public void deleteBook(@AuthenticationPrincipal UserPrincipal user, @PathVariable Long id) {
        requireAdmin(user);
        bookService.delete(id);
    }

    @DeleteMapping
    public void deleteBookByQuery(@AuthenticationPrincipal UserPrincipal user, @RequestParam Long id) {
        requireAdmin(user);
        bookService.delete(id);
    }

    @GetMapping("/{id}/stream")
    public ResponseEntity<Resource> streamBook(@PathVariable Long id) {
        return bookService.streamPdf(id);
    }

    @GetMapping("/{id}/pages/meta")
    public BookPageMetaResponse getPageMeta(@PathVariable Long id) {
        return new BookPageMetaResponse(id, bookService.getPageCount(id));
    }

    @GetMapping("/{id}/pages/{pageNumber}")
    public ResponseEntity<byte[]> getPageImage(@PathVariable Long id, @PathVariable Integer pageNumber) {
        return bookService.renderPageImage(id, pageNumber);
    }

    @GetMapping("/{id}/cover")
    public ResponseEntity<Resource> streamCover(@PathVariable Long id) {
        return bookService.streamCover(id);
    }

    private void requireAdmin(UserPrincipal user) {
        if (user == null || user.getRole() != Role.ADMIN) {
            throw new SecurityException("Hanya admin yang boleh mengakses");
        }
    }

    private BookResponse toResponse(Book book) {
        String cover = (book.getCover() == null || book.getCover().isBlank())
                ? "/api/books/" + book.getId() + "/cover"
                : book.getCover();
        return new BookResponse(book.getId(), book.getTitle(), book.getAuthor(), cover, book.getCategory());
    }
}
