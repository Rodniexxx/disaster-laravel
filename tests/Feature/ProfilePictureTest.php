<?php

namespace Tests\Feature;

use App\Models\PlayerProfile;
use App\Models\User;
use App\Services\AvatarService;
use App\Services\ReadinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProfilePictureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('s3');
        config(['profile.uploads_enabled'=>true,'profile.avatar_disk'=>'s3','filesystems.disks.s3.bucket'=>'test-avatars']);
    }

    private function registration(array $extra = []): array
    {
        return [...['username'=>'newplayer','email'=>'new@example.test','password'=>'secret123','password_confirmation'=>'secret123'], ...$extra];
    }

    private function picture(User $user, ?UploadedFile $file = null, int $revision = 0, bool $remove = false)
    {
        return $this->actingAs($user, 'web')->from('/profile')->post('/profile/picture', [
            '_method'=>'PATCH','expected_revision'=>$revision,'remove_profile_picture'=>$remove,
            ...($file ? ['profile_picture'=>$file] : []),
        ]);
    }

    public function test_registration_without_photo_preserves_the_existing_flow_and_default_avatar(): void
    {
        $this->post('/register', $this->registration())->assertRedirect('/login')->assertSessionHasNoErrors();
        $user = User::where('username','Newplayer')->firstOrFail();
        $this->assertStringEndsWith('/images/default-avatar.png', $user->profile_picture_url);
        $this->assertDatabaseCount('player_profiles', 0);
        $this->assertDatabaseCount('leaderboard', 0);
        $this->assertGuest();
    }

    public function test_registration_with_photo_creates_owned_processed_file_and_can_serve_it_publicly(): void
    {
        $this->post('/register', $this->registration(['profile_picture'=>UploadedFile::fake()->image('photo.png', 120, 100)]))->assertRedirect('/login');
        $user = User::where('username','Newplayer')->firstOrFail(); $profile = $user->playerProfile;
        $this->assertTrue(app(AvatarService::class)->owned((int)$user->id, $profile->avatar_path));
        Storage::disk('s3')->assertExists($profile->avatar_path);
        $this->get(parse_url($user->profile_picture_url, PHP_URL_PATH))->assertOk()
            ->assertHeader('Content-Type','image/jpeg')->assertHeader('X-Content-Type-Options','nosniff');
        $this->get('/api/profile/avatar')->assertUnauthorized();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_web_upload_replacement_removal_and_api_refresh_share_one_profile_without_changing_game_data(): void
    {
        $user = $this->makeUser();
        app(ReadinessService::class)->submit($user, ['attempt_id'=>(string)Str::uuid(),'scenario'=>'Fire','completed'=>true,'quiz_correct'=>8,'quiz_total'=>10,
            'metrics'=>array_map(fn($id)=>['id'=>$id,'earned'=>1,'possible'=>1], ReadinessService::CRITERIA['Fire'])]);
        $before = DB::table('readiness_progress')->get()->toJson();
        $account = $user->only('username','email','password_hash');
        $this->picture($user, UploadedFile::fake()->image('a.jpg'))->assertRedirect('/profile');
        $first = $user->fresh()->profile_picture_url; $path = PlayerProfile::find($user->id)->avatar_path;
        $this->picture($user, UploadedFile::fake()->image('b.webp'), 1)->assertRedirect('/profile');
        $new = $user->fresh()->profile_picture_url;
        $this->assertNotSame($first,$new); Storage::disk('s3')->assertMissing($path);
        $this->get(parse_url($first, PHP_URL_PATH))->assertNotFound();
        $this->getJson('/api/profile')->assertOk()->assertJsonPath('profile.profile_picture_url',$new)->assertJsonPath('profile.revision',2);
        $this->getJson('/api/me')->assertOk()->assertJsonPath('user.profile_picture_url',$new);
        $secondPath=PlayerProfile::find($user->id)->avatar_path;
        $this->picture($user, revision:2, remove:true)->assertRedirect('/profile');
        Storage::disk('s3')->assertMissing($secondPath);
        $this->assertStringEndsWith('/images/default-avatar.png',$user->fresh()->profile_picture_url);
        $this->assertSame($account,$user->fresh()->only('username','email','password_hash'));
        $this->assertSame($before,DB::table('readiness_progress')->get()->toJson());
        $this->assertDatabaseCount('readiness_attempts',1);
    }

    public function test_uploads_validate_real_type_size_corruption_and_dimensions_without_creating_accounts(): void
    {
        foreach ([
            UploadedFile::fake()->createWithContent('photo.png','<svg onload="alert(1)"></svg>'),
            UploadedFile::fake()->createWithContent('photo.jpg','not an image'),
            UploadedFile::fake()->image('large.jpg')->size(2049),
            UploadedFile::fake()->image('wide.png',4097,1),
        ] as $file) {
            $this->from('/register')->post('/register',$this->registration(['profile_picture'=>$file]))->assertSessionHasErrors('profile_picture');
        }
        $this->assertDatabaseCount('users',0); $this->assertDatabaseCount('player_profiles',0);
    }

    public function test_processed_upload_strips_trailing_script_and_unique_versions_prevent_overwrites(): void
    {
        $user=$this->makeUser();
        $raw=UploadedFile::fake()->image('photo.png')->getContent().'<?php avatar_script_sentinel(); ?>';
        $this->picture($user,UploadedFile::fake()->createWithContent('same.png',$raw))->assertSessionHasNoErrors();
        $path=PlayerProfile::find($user->id)->avatar_path;
        $this->assertStringNotContainsString('avatar_script_sentinel',Storage::disk('s3')->get($path));
        $other=$this->makeUser(); $this->picture($other,UploadedFile::fake()->image('same.png'))->assertSessionHasNoErrors();
        $this->assertNotSame($path,PlayerProfile::find($other->id)->avatar_path);
    }

    public function test_other_players_and_shared_defaults_cannot_be_modified_or_deleted(): void
    {
        $a=$this->makeUser(); $b=$this->makeUser();
        $this->picture($a,UploadedFile::fake()->image('a.jpg'))->assertSessionHasNoErrors();
        $owned=PlayerProfile::find($a->id); $path=$owned->avatar_path;
        $this->actingAs($b,'web')->post('/profile/picture',['_method'=>'PATCH','expected_revision'=>0,'user_id'=>$a->id,'remove_profile_picture'=>true])->assertSessionHasErrors('user_id');
        Storage::disk('s3')->assertExists($path);
        PlayerProfile::create(['user_id'=>$b->id,'display_name'=>$b->username,'avatar_disk'=>'s3','avatar_path'=>$path,'avatar_version'=>$owned->avatar_version,'revision'=>1]);
        $this->picture($b,revision:1,remove:true)->assertSessionHasNoErrors();
        Storage::disk('s3')->assertExists($path);
        Storage::disk('s3')->put('images/default-avatar.svg','shared');
        PlayerProfile::where('user_id',$b->id)->update(['avatar_path'=>'images/default-avatar.svg','avatar_disk'=>'s3','avatar_version'=>(string)Str::uuid()]);
        $this->picture($b,revision:2,remove:true)->assertSessionHasNoErrors();
        Storage::disk('s3')->assertExists('images/default-avatar.svg');
    }

    public function test_failed_storage_and_stale_updates_keep_the_previous_picture_and_account(): void
    {
        $user=$this->makeUser();$this->picture($user,UploadedFile::fake()->image('a.jpg'))->assertSessionHasNoErrors();
        $original=PlayerProfile::find($user->id)->toArray();
        $this->picture($user,UploadedFile::fake()->image('stale.jpg'),0)->assertSessionHasErrors('profile_picture');
        $disk=\Mockery::mock();$disk->shouldReceive('put')->once()->andThrow(new \RuntimeException('offline'));
        $disk->shouldReceive('delete')->once()->andReturn(true);Storage::shouldReceive('disk')->with('s3')->andReturn($disk);
        $this->picture($user,UploadedFile::fake()->image('fail.jpg'),1)->assertSessionHasErrors('profile_picture');
        $this->assertSame($original,PlayerProfile::find($user->id)->toArray());
    }

    public function test_failed_registration_photo_storage_rolls_back_the_new_account(): void
    {
        $disk=\Mockery::mock();$disk->shouldReceive('put')->once()->andReturn(false);$disk->shouldReceive('delete')->once()->andReturn(true);
        Storage::shouldReceive('disk')->with('s3')->andReturn($disk);
        $this->post('/register',$this->registration(['profile_picture'=>UploadedFile::fake()->image('a.jpg')]))->assertSessionHasErrors('profile_picture');
        $this->assertDatabaseCount('users',0);$this->assertDatabaseCount('player_profiles',0);
    }

    public function test_api_fields_are_additive_https_in_production_and_persist_across_login(): void
    {
        config(['profile.public_base_url'=>'https://mangno.example']);
        $user=$this->makeUser(['password'=>'secret123']);$this->picture($user,UploadedFile::fake()->image('a.png'))->assertSessionHasNoErrors();
        $url=$user->fresh()->profile_picture_url;
        Auth::forgetGuards();
        $login=$this->postJson('/api/login',['username'=>$user->username,'password'=>'secret123'])->assertOk()
            ->assertJsonPath('profile_picture_url',$url)->assertJsonPath('user_id',$user->id)->assertJsonPath('email',$user->email)->assertJsonPath('role','user');
        $this->assertStringStartsWith('https://mangno.example/profile-pictures/',$url);
        $this->assertStringNotContainsString('avatars/',$url);
        $token=$login->json('token');Auth::forgetGuards();
        $this->withToken($token)->getJson('/api/me')->assertOk()->assertJsonPath('user.profile_picture_url',$url);
        Auth::forgetGuards();$this->withToken($token)->getJson('/api/profile')->assertOk()->assertJsonPath('profile.profile_picture_url',$url);
    }

    public function test_avatar_ui_and_storage_reader_work_after_service_is_recreated(): void
    {
        $user=$this->makeUser();$this->picture($user,UploadedFile::fake()->image('a.png'))->assertSessionHasNoErrors();
        $url=$user->fresh()->profile_picture_url;$profile=PlayerProfile::find($user->id);
        $this->assertSame($url,(new AvatarService)->url((int)$user->id,$profile));
        $this->actingAs($user,'web')->get('/profile')->assertOk()->assertSee($url,false)->assertSee('Save picture');
        $this->get('/leaderboard')->assertOk()->assertSee($url,false);
        $this->actingAs($this->makeAdmin(),'web')->get('/admin/users')->assertOk()->assertSee($url,false);
        $this->get('/admin/progress')->assertOk()->assertSee($url,false);
    }

    public function test_production_uses_the_canonical_https_host_for_default_and_uploaded_pictures(): void
    {
        $this->app['env']='production';
        config(['profile.public_base_url'=>null,'app.url'=>'http://mangno.example']);
        $user=$this->makeUser();
        $this->assertSame('https://mangno.example/images/default-avatar.png',$user->profile_picture_url);
        $avatars=app(AvatarService::class);
        $avatars->save($user,$avatars->prepare(UploadedFile::fake()->image('avatar.jpg')));
        $this->assertStringStartsWith('https://mangno.example/profile-pictures/',$user->fresh()->profile_picture_url);
    }

    public function test_local_docker_preserves_http_scheme_and_port_for_default_and_uploaded_photos(): void
    {
        $this->app['env'] = 'production';
        config(['profile.public_base_url' => null, 'app.url' => 'http://localhost:8000']);
        $user = $this->makeUser();
        $avatars = app(AvatarService::class);
        $this->assertSame('http://localhost:8000/images/default-avatar.png', $user->profile_picture_url);
        $avatars->save($user, $avatars->prepare(UploadedFile::fake()->image('avatar.jpg')));
        $url = $user->fresh()->profile_picture_url;
        $this->assertStringStartsWith('http://localhost:8000/profile-pictures/', $url);
        $this->get(parse_url($url, PHP_URL_PATH))->assertOk()->assertHeader('Content-Type', 'image/jpeg');
    }
}
