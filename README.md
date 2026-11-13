# Entry Revisions

Outputs the saved revisions of channel entries on the front end of an ExpressionEngine 7 site.

Revisions only exist for channels with **Versioning** enabled (Channel → Settings → *Enable entry versioning?*).

**Compatibility:** ExpressionEngine 7 (tested against 7.5), PHP 7.4+.

## Installation

1. Copy the `entry_revisions` folder to `system/user/addons/`.
2. In the Control Panel go to **Add-Ons** and click **Install** on *Entry Revisions*.

## Example

```
{exp:entry_revisions channel="blog" url_title="{segment_2}" limit="5"}
    {if revision:count == 1}<ul>{/if}
    <li>
        <strong>{revision:title}</strong>
        saved {revision:version_date format="%d %M %Y %H:%i"}
        {revision:body}
    </li>
    {if revision:count == revision:total_counts}</ul>{/if}
    {if no_results}<p>No revisions yet.</p>{/if}
{/exp:entry_revisions}
```

## Parameters

| Parameter | Description |
|---|---|
| `channel` | Channel short name or ID. Pipe-separate for several: `blog\|news` |
| `entry_id` | Entry ID(s), pipe-separated |
| `url_title` | URL title(s) of the entry, pipe-separated |
| `author_id` | Member ID(s) who saved the revision, pipe-separated |
| `version_id` | Specific revision ID(s), pipe-separated |
| `version_date` | Exact revision timestamp(s), e.g. `version_date="{segment_3}"` |
| `status` | Entry status. Default `open`. Pipe-separate several, prefix with `not ` to exclude, or use `any` |
| `site_id` | Default: current site |
| `version_date_sort` | `asc` or `desc`. Default `desc` |
| `limit` | Default `10` |
| `offset` | Default `0` |
| `multi_separator` | Separator for multi-value fields (checkboxes, multi-select). Default `, ` |

## Variables

| Variable | Description |
|---|---|
| `{revision:title}` | Title as saved in the revision |
| `{revision:url_title}` | URL title as saved in the revision |
| `{revision:version_date format="%Y-%m-%d"}` | Revision date (standard date formatting) |
| `{revision:version_id}` | Revision ID |
| `{revision:entry_id}` | Entry ID |
| `{revision:channel_id}` | Channel ID |
| `{revision:author_id}` | Member ID who saved the revision |
| `{revision:count}` | 1, 2, 3… |
| `{revision:total_counts}` / `{revision:total_results}` | Number of revisions returned |
| `{revision:your_field_name}` | Raw value of any custom field in the revision |

Custom field values are output raw (unformatted). Fields that keep their data in separate tables or nested structures (Grid, File Grid, Fluid, Relationships) are not available from revision data and output nothing.

## Changelog

**2.0.0**
- ExpressionEngine 7 support: `addon.setup.php`, `ee()` service calls, no `$plugin_info`.
- Uses the site's configured database prefix (previously hard-coded `exp_`).
- All parameters now escaped / cast; fixes SQL injection via `author_id`, `site_id`, `limit`, sort.
- Fixed `{revision:count}` always returning 1.
- Custom fields now resolved for the requested site (including global fields), not just the first site.
- Multi-value fields no longer break parsing.
- New parameters: `version_id`, `version_date`, `offset`, `multi_separator`, `status="not …"` / `any`.
- New variables: `version_id`, `channel_id`, `author_id`, `total_results`.

**1.0.1** – EE2 release.
