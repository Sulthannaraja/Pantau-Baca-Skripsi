package com.elibrary.service;

import com.elibrary.dto.BookRequest;
import com.elibrary.model.Book;
import com.elibrary.repository.BookRepository;
import lombok.RequiredArgsConstructor;
import org.apache.pdfbox.Loader;
import org.apache.pdfbox.pdmodel.PDDocument;
import org.apache.pdfbox.rendering.PDFRenderer;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.core.io.Resource;
import org.springframework.core.io.UrlResource;
import org.springframework.http.HttpHeaders;
import org.springframework.http.MediaType;
import org.springframework.http.ResponseEntity;
import org.springframework.stereotype.Service;
import org.springframework.web.multipart.MultipartFile;

import java.io.IOException;
import java.io.ByteArrayOutputStream;
import java.awt.image.BufferedImage;
import java.net.MalformedURLException;
import java.nio.file.Files;
import java.nio.file.Path;
import java.nio.file.Paths;
import java.nio.file.StandardCopyOption;
import java.util.List;
import java.util.UUID;

@Service
@RequiredArgsConstructor
public class BookService {
    private final BookRepository bookRepository;

    @Value("${app.upload-dir:uploads/books}")
    private String uploadDir;
    @Value("${app.cover-dir:uploads/covers}")
    private String coverDir;

    public List<Book> getAll() {
        return bookRepository.findAll();
    }

    public Book getById(Long id) {
        return bookRepository.findById(id).orElseThrow(() -> new IllegalArgumentException("Buku tidak ditemukan"));
    }

    public Book create(BookRequest request) {
        Book b = new Book();
        b.setTitle(request.title());
        b.setAuthor(request.author());
        b.setFilePath(request.filePath());
        b.setCover(request.cover());
        b.setCategory(request.category());
        return bookRepository.save(b);
    }

    public Book createWithUpload(String title, String author, String category, String cover, MultipartFile file) {
        try {
            if (title == null || title.isBlank() || author == null || author.isBlank()) {
                throw new IllegalArgumentException("Judul dan penulis wajib diisi");
            }
            if (file == null || file.isEmpty()) {
                throw new IllegalArgumentException("File PDF wajib dipilih");
            }
            Path dir = Paths.get(uploadDir).toAbsolutePath().normalize();
            Files.createDirectories(dir);

            String filename = UUID.randomUUID() + "-" + file.getOriginalFilename();
            Path target = dir.resolve(filename).normalize();
            if (!target.startsWith(dir)) {
                throw new IllegalArgumentException("Path file tidak aman");
            }

            Files.copy(file.getInputStream(), target, StandardCopyOption.REPLACE_EXISTING);

            Book b = new Book();
            b.setTitle(title);
            b.setAuthor(author);
            b.setCategory(category);
            b.setCover(cover);
            b.setFilePath(target.toString());
            Book saved = bookRepository.save(b);
            generateCover(saved.getId(), target);
            saved.setCover("/api/books/" + saved.getId() + "/cover");
            return bookRepository.save(saved);
        } catch (IOException e) {
            throw new RuntimeException("Gagal upload PDF", e);
        }
    }

    public void delete(Long id) {
        Book book = getById(id);
        bookRepository.delete(book);

        deleteIfExists(Paths.get(book.getFilePath()));
        deleteIfExists(getCoverPath(id));
    }

    public ResponseEntity<Resource> streamPdf(Long id) {
        Book book = getById(id);
        Path path = Paths.get(book.getFilePath()).toAbsolutePath().normalize();
        Resource resource;
        try {
            resource = new UrlResource(path.toUri());
        } catch (MalformedURLException e) {
            throw new IllegalArgumentException("Path file buku tidak valid");
        }

        if (!resource.exists()) {
            throw new IllegalArgumentException("File buku tidak ditemukan di server");
        }

        return ResponseEntity.ok()
                .contentType(MediaType.APPLICATION_PDF)
                .header(HttpHeaders.CONTENT_DISPOSITION, "inline; filename=book-" + id + ".pdf")
                .header("X-Content-Type-Options", "nosniff")
                .header("Cache-Control", "no-store")
                .body(resource);
    }

    public ResponseEntity<Resource> streamCover(Long id) {
        Book book = getById(id);
        Path coverPath = getCoverPath(id);
        if (!Files.exists(coverPath)) {
            try {
                generateCover(id, Paths.get(book.getFilePath()));
            } catch (IOException e) {
                throw new IllegalArgumentException("Cover buku belum tersedia");
            }
        }

        try {
            Resource resource = new UrlResource(coverPath.toUri());
            return ResponseEntity.ok()
                    .contentType(MediaType.IMAGE_JPEG)
                    .header("Cache-Control", "public, max-age=86400")
                    .body(resource);
        } catch (MalformedURLException e) {
            throw new IllegalArgumentException("Cover tidak valid");
        }
    }

    public int getPageCount(Long id) {
        Book book = getById(id);
        Path pdfPath = Paths.get(book.getFilePath()).toAbsolutePath().normalize();
        try (PDDocument document = Loader.loadPDF(pdfPath.toFile())) {
            return document.getNumberOfPages();
        } catch (IOException e) {
            throw new IllegalArgumentException("Gagal membaca jumlah halaman buku");
        }
    }

    public ResponseEntity<byte[]> renderPageImage(Long id, int pageNumber) {
        Book book = getById(id);
        Path pdfPath = Paths.get(book.getFilePath()).toAbsolutePath().normalize();

        try (PDDocument document = Loader.loadPDF(pdfPath.toFile());
             ByteArrayOutputStream outputStream = new ByteArrayOutputStream()) {
            int totalPages = document.getNumberOfPages();
            if (pageNumber < 1 || pageNumber > totalPages) {
                throw new IllegalArgumentException("Halaman buku tidak valid");
            }

            PDFRenderer renderer = new PDFRenderer(document);
            BufferedImage image = renderer.renderImageWithDPI(pageNumber - 1, 140);
            javax.imageio.ImageIO.write(image, "jpg", outputStream);

            return ResponseEntity.ok()
                    .contentType(MediaType.IMAGE_JPEG)
                    .header("Cache-Control", "no-store")
                    .body(outputStream.toByteArray());
        } catch (IOException e) {
            throw new IllegalArgumentException("Gagal merender halaman buku");
        }
    }

    private void generateCover(Long bookId, Path pdfPath) throws IOException {
        Path coverFolder = Paths.get(coverDir).toAbsolutePath().normalize();
        Files.createDirectories(coverFolder);
        Path coverPath = getCoverPath(bookId);
        try (PDDocument document = Loader.loadPDF(pdfPath.toFile())) {
            PDFRenderer renderer = new PDFRenderer(document);
            BufferedImage image = renderer.renderImageWithDPI(0, 140);
            javax.imageio.ImageIO.write(image, "jpg", coverPath.toFile());
        }
    }

    private Path getCoverPath(Long id) {
        return Paths.get(coverDir).toAbsolutePath().normalize().resolve("book-" + id + ".jpg");
    }

    private void deleteIfExists(Path path) {
        try {
            Files.deleteIfExists(path.toAbsolutePath().normalize());
        } catch (IOException ignored) {
            // Cleanup failure should not block data deletion.
        }
    }
}
