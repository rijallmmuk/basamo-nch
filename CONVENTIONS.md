# CONVENTIONS.md — Smart Learning Center Basamo NCH

> Standar kode yang wajib diikuti seluruh tim dan semua agent.
> Pint (.pint.json) di-setup untuk enforce sebagian dari ini secara otomatis.

---

## PHP & Laravel

### Urutan method di Model

```php
class UmkmProduct extends Model
{
    // 1. Traits
    use HasFactory, SoftDeletes, InteractsWithMedia;

    // 2. Constants
    const STATUS_PENDING  = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    // 3. Fillable / guarded
    protected $fillable = [...];

    // 4. Casts
    protected $casts = [...];

    // 5. Scopes
    public function scopeApproved(Builder $query): Builder {...}
    public function scopeByNagari(Builder $query, int $nagariId): Builder {...}

    // 6. Relationships
    public function umkmProfile(): BelongsTo {...}
    public function photos(): HasMany {...}

    // 7. Accessors / Mutators
    public function getStatusLabelAttribute(): string {...}

    // 8. Methods
    public function isApproved(): bool {...}
}
```

### Service class pattern

```php
class LmsProgressService
{
    // Inject dependencies via constructor
    public function __construct(
        private readonly LmsPointService $pointService,
    ) {}

    // Satu method = satu tanggung jawab
    public function completePage(User $user, ModulePage $page): UserModuleProgress
    {
        // Validasi dulu
        // Logic
        // Return result (bukan void jika ada data yang dikembalikan)
    }
}
```

### Controller — tipis, hanya delegasi

```php
public function store(StoreQuizAttemptRequest $request, Module $module)
{
    $attempt = $this->quizService->submitAttempt(
        user: auth()->user(),
        quiz: $module->quiz,
        answers: $request->validated('answers'),
    );

    return redirect()->route('portal.modules.show', $module)
        ->with('success', 'Kuis berhasil dikumpulkan.');
}
```

---

## Database

### Wajib di setiap migration tabel baru

```php
// 1. Selalu ada nagari_id (kecuali tabel global)
$table->foreignId('nagari_id')->constrained()->cascadeOnDelete();

// 2. Index nagari_id wajib
$table->index('nagari_id');

// 3. Timestamps selalu ada
$table->timestamps();

// 4. SoftDeletes untuk data penting (user, UMKM, modul, SDGs)
$table->softDeletes();
```

### Eloquent Query — selalu eager load

```php
// ✅ BENAR
$modules = Module::with(['pages', 'quiz.questions'])->get();

// ❌ SALAH — N+1 problem
$modules = Module::all();
foreach ($modules as $module) {
    $module->pages; // query baru per modul
}
```

---

## Filament Resources

### Resource wajib define `getEloquentQuery()` untuk Admin Nagari

```php
// Di setiap Resource yang diakses Admin Nagari
public static function getEloquentQuery(): Builder
{
    $query = parent::getEloquentQuery();

    // Jika bukan super admin, filter ke nagari sendiri
    if (!auth()->user()->hasRole('super_admin')) {
        $query->where('nagari_id', auth()->user()->nagari_id);
    }

    return $query;
}
```

### Form fields — gunakan label Bahasa Indonesia

```php
Forms\Components\TextInput::make('nama_usaha')
    ->label('Nama Usaha')
    ->placeholder('Contoh: Keripik Balado Uni Sari')
    ->required()
    ->maxLength(255),
```

---

## Blade & Tailwind

### Komponen portal warga

```php
// Gunakan Blade components untuk UI yang berulang
// resources/views/components/module-card.blade.php
<x-module-card :module="$module" />

// Jangan inline style — selalu Tailwind class
// ✅ BENAR
<div class="rounded-xl bg-white shadow-sm border border-gray-100 p-4">

// ❌ SALAH
<div style="border-radius: 12px; background: white;">
```

### Warna yang dipakai (konsisten dari ui-guide)

```
Primary   : indigo-600   #4F46E5
Success   : green-600    #16A34A
Warning   : amber-500    #F59E0B
Danger    : red-600      #DC2626
Info      : blue-600     #2563EB
Neutral   : gray-600     #4B5563
```

---

## Testing (Pest)

```php
// Setiap Service wajib ada test
it('menambahkan poin saat warga menyelesaikan halaman modul', function () {
    $warga = User::factory()->warga()->create();
    $page  = ModulePage::factory()->create();

    $service = new LmsPointService();
    $service->awardPageCompletion($warga, $page);

    expect($warga->fresh()->total_points)->toBe(10);
});

// Test multi-tenancy — wajib ada
it('admin nagari tidak bisa lihat data nagari lain', function () {
    $nagariA = Nagari::factory()->create();
    $nagariB = Nagari::factory()->create();
    $adminA  = User::factory()->nagariAdmin()->for($nagariA)->create();
    $productB = UmkmProduct::factory()->for($nagariB)->create();

    actingAs($adminA)
        ->get(route('filament.admin.resources.umkm-products.index'))
        ->assertDontSee($productB->nama_produk);
});
```

---

## Git

```bash
# Branch dari main selalu
git checkout main && git pull
git checkout -b feature/nama-fitur

# Commit kecil, sering, deskriptif
git add -p                        # staging per hunk, bukan git add .
git commit -m "feat: deskripsi singkat perubahan"

# Sebelum push, format dulu
./vendor/bin/pint
git add -p
git commit -m "chore: pint format"

# Jangan push ke main langsung
git push origin feature/nama-fitur
```

### Format commit message

```
feat:   fitur baru
fix:    perbaikan bug
chore:  maintenance, dependency, config
docs:   update dokumentasi / agent files
test:   tambah atau perbaiki test
refactor: refactor tanpa perubahan behavior
```
