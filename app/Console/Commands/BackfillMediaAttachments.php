<?php

namespace App\Console\Commands;

use App\Services\MediaAttachmentService;
use Illuminate\Console\Command;

/**
 * Retroactively links every package's existing ad-hoc file-storage columns
 * to a real row in the shared `media` table, so old files become visible
 * and quota-counted in the Media Library without moving any data on disk.
 *
 * Safe to re-run: each source is skipped row-by-row once its media_id
 * column is set (see the whereNull($mediaIdColumn) filter below).
 */
class BackfillMediaAttachments extends Command
{
    protected $signature = 'media:backfill {source? : source map key, or "all"} {--dry-run : log intended actions without writing}';

    protected $description = 'Backfill media_id links for every package\'s ad-hoc file-storage columns';

    /**
     * One entry per table/column being unified. Filled in incrementally as
     * each package is migrated — see the plan for the full rollout order.
     *
     * Each entry:
     *   iterate_model    Fully-qualified Eloquent model class to loop over (has path_column/media_id_column).
     *   path_column      Column holding the existing bare file_name / subpath string.
     *   media_id_column  Column to set once linked.
     *   collection       Media collection_name to assign.
     *   directory        Auto-created top-level MediaDirectory name.
     *   owner_model      Fully-qualified class name recorded as Media.model_type (the real business owner,
     *                    which may differ from iterate_model — e.g. a Contract, not its ContractAttachment row).
     *   owner_id         Closure(mixed $row): int — resolves the owner's id from the iterated row.
     *   creator_of       Closure(mixed $row): array{?int, ?int} => [creator_id, created_by].
     *   exclude_values   Optional array of path_column values to skip (e.g. a shared default placeholder).
     */
    protected function sources(): array
    {
        return [
            'contract_attachments' => [
                'iterate_model' => \Zerp\Contract\Models\ContractAttachment::class,
                'path_column' => 'file_name',
                'media_id_column' => 'media_id',
                'collection' => 'contract_attachments',
                'directory' => 'Contract Attachments',
                'owner_model' => \Zerp\Contract\Models\Contract::class,
                'owner_id' => fn ($row) => $row->contract_id,
                'creator_of' => fn ($row) => [$row->creator_id, $row->created_by],
            ],
            'employee_documents' => [
                'iterate_model' => \Zerp\Hrm\Models\EmployeeDocument::class,
                'path_column' => 'file_path',
                'media_id_column' => 'media_id',
                'collection' => 'employee_documents',
                'directory' => 'Employee Documents',
                'owner_model' => \Zerp\Hrm\Models\Employee::class,
                'owner_id' => fn ($row) => $row->user_id,
                'creator_of' => fn ($row) => [$row->creator_id, $row->created_by],
            ],
            'leave_applications' => [
                'iterate_model' => \Zerp\Hrm\Models\LeaveApplication::class,
                'path_column' => 'attachment',
                'media_id_column' => 'media_id',
                'collection' => 'leave_attachments',
                'directory' => 'Leave Attachments',
                'owner_model' => \Zerp\Hrm\Models\LeaveApplication::class,
                'owner_id' => fn ($row) => $row->id,
                'creator_of' => fn ($row) => [$row->creator_id, $row->created_by],
            ],
            'hrm_documents' => [
                'iterate_model' => \Zerp\Hrm\Models\HrmDocument::class,
                'path_column' => 'document',
                'media_id_column' => 'media_id',
                'collection' => 'hrm_documents',
                'directory' => 'HRM Documents',
                'owner_model' => \Zerp\Hrm\Models\HrmDocument::class,
                'owner_id' => fn ($row) => $row->id,
                'creator_of' => fn ($row) => [$row->creator_id, $row->created_by],
            ],
            'project_files' => [
                'iterate_model' => \Zerp\Taskly\Models\ProjectFile::class,
                'path_column' => 'file_path',
                'media_id_column' => 'media_id',
                'collection' => 'project_files',
                'directory' => 'Project Files',
                'owner_model' => \Zerp\Taskly\Models\Project::class,
                'owner_id' => fn ($row) => $row->project_id,
                'creator_of' => fn ($row) => $row->project ? [$row->project->creator_id, $row->project->created_by] : [null, null],
            ],
            'lead_files' => [
                'iterate_model' => \Zerp\Lead\Models\LeadFile::class,
                'path_column' => 'file_path',
                'media_id_column' => 'media_id',
                'collection' => 'lead_files',
                'directory' => 'Lead Files',
                'owner_model' => \Zerp\Lead\Models\Lead::class,
                'owner_id' => fn ($row) => $row->lead_id,
                'creator_of' => fn ($row) => $row->lead ? [$row->lead->creator_id, $row->lead->created_by] : [null, null],
            ],
            'deal_files' => [
                'iterate_model' => \Zerp\Lead\Models\DealFile::class,
                'path_column' => 'file_path',
                'media_id_column' => 'media_id',
                'collection' => 'deal_files',
                'directory' => 'Deal Files',
                'owner_model' => \Zerp\Lead\Models\Deal::class,
                'owner_id' => fn ($row) => $row->deal_id,
                'creator_of' => fn ($row) => $row->deal ? [$row->deal->creator_id, $row->deal->created_by] : [null, null],
            ],
            'candidates_profile' => [
                'iterate_model' => \Zerp\Recruitment\Models\Candidate::class,
                'path_column' => 'profile_path',
                'media_id_column' => 'profile_media_id',
                'collection' => 'candidate_profiles',
                'directory' => 'Candidate Profiles',
                'owner_model' => \Zerp\Recruitment\Models\Candidate::class,
                'owner_id' => fn ($row) => $row->id,
                'creator_of' => fn ($row) => [$row->creator_id, $row->created_by],
            ],
            'candidates_resume' => [
                'iterate_model' => \Zerp\Recruitment\Models\Candidate::class,
                'path_column' => 'resume_path',
                'media_id_column' => 'resume_media_id',
                'collection' => 'candidate_resumes',
                'directory' => 'Candidate Resumes',
                'owner_model' => \Zerp\Recruitment\Models\Candidate::class,
                'owner_id' => fn ($row) => $row->id,
                'creator_of' => fn ($row) => [$row->creator_id, $row->created_by],
            ],
            'candidates_cover_letter' => [
                'iterate_model' => \Zerp\Recruitment\Models\Candidate::class,
                'path_column' => 'cover_letter_path',
                'media_id_column' => 'cover_letter_media_id',
                'collection' => 'candidate_cover_letters',
                'directory' => 'Candidate Cover Letters',
                'owner_model' => \Zerp\Recruitment\Models\Candidate::class,
                'owner_id' => fn ($row) => $row->id,
                'creator_of' => fn ($row) => [$row->creator_id, $row->created_by],
            ],
            'bank_transfer_payments' => [
                'iterate_model' => \App\Models\BankTransferPayment::class,
                'path_column' => 'attachment',
                'media_id_column' => 'media_id',
                'collection' => 'bank_transfer_receipts',
                'directory' => 'Bank Transfer Receipts',
                'owner_model' => \App\Models\BankTransferPayment::class,
                'owner_id' => fn ($row) => $row->id,
                'creator_of' => fn ($row) => [$row->user_id, $row->created_by],
            ],
            'add_ons' => [
                'iterate_model' => \App\Models\AddOn::class,
                'path_column' => 'image',
                'media_id_column' => 'media_id',
                'collection' => 'addon_images',
                'directory' => 'Add-on Images',
                'owner_model' => \App\Models\AddOn::class,
                'owner_id' => fn ($row) => $row->id,
                // Module registry is system-wide, not company-scoped — attribute to the superadmin.
                'creator_of' => function ($row) {
                    $superadminId = \App\Models\User::where('type', 'superadmin')->value('id');
                    return [$superadminId, $superadminId];
                },
            ],
            'ch_messages' => [
                'iterate_model' => \App\Models\ChMessage::class,
                'path_column' => 'attachment',
                'media_id_column' => 'media_id',
                'collection' => 'chat_attachments',
                'directory' => 'Chat Attachments',
                'owner_model' => \App\Models\ChMessage::class,
                'owner_id' => fn ($row) => $row->id,
                'creator_of' => fn ($row) => [$row->from_id, $row->from_id],
            ],
            'users_avatar' => [
                'iterate_model' => \App\Models\User::class,
                'path_column' => 'avatar',
                'media_id_column' => 'avatar_media_id',
                'collection' => 'avatars',
                'directory' => 'User Avatars',
                'owner_model' => \App\Models\User::class,
                'owner_id' => fn ($row) => $row->id,
                'creator_of' => fn ($row) => [$row->id, $row->created_by ?? $row->id],
                // Skip the shared default placeholder — it isn't a real per-user upload.
                'exclude_values' => ['avatar.png'],
            ],
        ];
    }

    /**
     * JSON-array sources (support-ticket's `attachments` columns hold an
     * array of {name, path} elements per row, not a single path column) —
     * handled by a dedicated loop rather than the generic column-based map.
     */
    protected function jsonArraySources(): array
    {
        return [
            'tickets_attachments' => [
                'iterate_model' => \Zerp\SupportTicket\Models\Ticket::class,
                'json_column' => 'attachments',
                'collection' => 'support_tickets',
                'directory' => 'Support Tickets',
                'owner_model' => \Zerp\SupportTicket\Models\Ticket::class,
                'owner_id' => fn ($row) => $row->id,
                'creator_of' => fn ($row) => [$row->creator_id, $row->created_by],
            ],
            'conversions_attachments' => [
                'iterate_model' => \Zerp\SupportTicket\Models\Conversion::class,
                'json_column' => 'attachments',
                'collection' => 'support_ticket_conversions',
                'directory' => 'Support Ticket Replies',
                'owner_model' => \Zerp\SupportTicket\Models\Conversion::class,
                'owner_id' => fn ($row) => $row->id,
                'creator_of' => fn ($row) => [$row->creator_id, $row->created_by],
            ],
        ];
    }

    public function handle(): int
    {
        $sourceKey = $this->argument('source');
        $dryRun = (bool) $this->option('dry-run');
        $sources = $this->sources();
        $jsonSources = $this->jsonArraySources();
        $all = array_merge($sources, $jsonSources);

        if (!$sourceKey || $sourceKey === 'all') {
            $keys = array_keys($all);
        } else {
            if (!isset($all[$sourceKey])) {
                $this->error("Unknown source \"{$sourceKey}\". Available: " . implode(', ', array_keys($all)));
                return self::FAILURE;
            }
            $keys = [$sourceKey];
        }

        if (empty($keys)) {
            $this->warn('No sources registered yet.');
            return self::SUCCESS;
        }

        foreach ($keys as $key) {
            if (isset($jsonSources[$key])) {
                $this->backfillJsonArraySource($key, $jsonSources[$key], $dryRun);
            } else {
                $this->backfillSource($key, $sources[$key], $dryRun);
            }
        }

        return self::SUCCESS;
    }

    protected function backfillJsonArraySource(string $key, array $source, bool $dryRun): void
    {
        $this->info("Backfilling: {$key}" . ($dryRun ? ' (dry run)' : ''));

        $iterateModel = $source['iterate_model'];
        $jsonColumn = $source['json_column'];
        $collection = $source['collection'];
        $directoryName = $source['directory'];
        $ownerModel = $source['owner_model'];
        $ownerId = $source['owner_id'];
        $creatorOf = $source['creator_of'];

        $linked = 0;
        $skippedNoOwner = 0;
        $rowsTouched = 0;

        $iterateModel::query()
            ->whereNotNull($jsonColumn)
            ->chunkById(200, function ($rows) use (&$linked, &$skippedNoOwner, &$rowsTouched, $jsonColumn, $collection, $directoryName, $ownerModel, $ownerId, $creatorOf, $dryRun) {
                foreach ($rows as $row) {
                    $attachments = is_array($row->$jsonColumn) ? $row->$jsonColumn : (json_decode($row->$jsonColumn ?? '[]', true) ?? []);
                    if (empty($attachments)) {
                        continue;
                    }

                    $needsLinking = false;
                    foreach ($attachments as $attachment) {
                        if (!empty($attachment['path']) && empty($attachment['media_id'])) {
                            $needsLinking = true;
                            break;
                        }
                    }
                    if (!$needsLinking) {
                        continue;
                    }

                    [$creatorId, $createdBy] = $creatorOf($row);
                    if (!$createdBy) {
                        $this->warn("  skip #{$row->id}: no owning company resolved");
                        $skippedNoOwner++;
                        continue;
                    }

                    if ($dryRun) {
                        $this->line("  would link attachments on #{$row->id}");
                        continue;
                    }

                    $linkedAttachments = \Zerp\SupportTicket\Models\Ticket::linkAttachmentsMedia(
                        $attachments,
                        $ownerModel,
                        $ownerId($row),
                        $collection,
                        $directoryName,
                        $creatorId,
                        $createdBy
                    );
                    $row->update([$jsonColumn => $linkedAttachments]);
                    $rowsTouched++;
                    $linked += count(array_filter($linkedAttachments, fn ($a) => !empty($a['media_id'])));
                }
            });

        $this->info("  rows_touched={$rowsTouched} attachments_linked={$linked} skipped_no_owner={$skippedNoOwner}");
    }

    protected function backfillSource(string $key, array $source, bool $dryRun): void
    {
        $this->info("Backfilling: {$key}" . ($dryRun ? ' (dry run)' : ''));

        /** @var \Illuminate\Database\Eloquent\Model $iterateModel */
        $iterateModel = $source['iterate_model'];
        $pathColumn = $source['path_column'];
        $mediaIdColumn = $source['media_id_column'];
        $collection = $source['collection'];
        $directoryName = $source['directory'];
        $ownerModel = $source['owner_model'];
        $ownerId = $source['owner_id'];
        $creatorOf = $source['creator_of'];
        $excludeValues = $source['exclude_values'] ?? [];

        $linked = 0;
        $skippedNoOwner = 0;
        $skippedNotFound = 0;

        $iterateModel::query()
            ->whereNotNull($pathColumn)
            ->where($pathColumn, '!=', '')
            ->when($excludeValues, fn ($q) => $q->whereNotIn($pathColumn, $excludeValues))
            ->whereNull($mediaIdColumn)
            ->chunkById(200, function ($rows) use (&$linked, &$skippedNoOwner, &$skippedNotFound, $pathColumn, $mediaIdColumn, $collection, $directoryName, $ownerModel, $ownerId, $creatorOf, $dryRun) {
                foreach ($rows as $row) {
                    [$creatorId, $createdBy] = $creatorOf($row);

                    if (!$createdBy) {
                        $this->warn("  skip #{$row->id}: no owning company resolved");
                        $skippedNoOwner++;
                        continue;
                    }

                    if ($dryRun) {
                        $this->line("  would link #{$row->id} ({$row->$pathColumn})");
                        continue;
                    }

                    $directoryId = MediaAttachmentService::ensureDirectory($directoryName, $createdBy, $creatorId);

                    $media = MediaAttachmentService::resolveOrBackfill(
                        $row->$pathColumn,
                        $ownerModel,
                        $ownerId($row),
                        $collection,
                        $creatorId,
                        $createdBy,
                        $directoryId
                    );

                    if ($media) {
                        $row->update([$mediaIdColumn => $media->id]);
                        $linked++;
                    } else {
                        $this->warn("  not found on any disk: #{$row->id} ({$row->$pathColumn})");
                        $skippedNotFound++;
                    }
                }
            });

        $this->info("  linked={$linked} skipped_no_owner={$skippedNoOwner} skipped_not_found={$skippedNotFound}");
    }
}
