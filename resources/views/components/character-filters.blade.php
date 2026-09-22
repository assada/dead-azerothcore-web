@props(['classes'])
<form class="character-filters" aria-label="Filter characters">
    <label>Class<select name="class"><option value="">All classes</option>@foreach($classes as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</select></label>
    <label>Guild<input name="guild" placeholder="Guild name" maxlength="100"></label>
    <fieldset><legend>Level</legend><div class="level-range"><input type="number" name="level_min" min="1" max="80" placeholder="1" aria-label="Minimum level"><span>–</span><input type="number" name="level_max" min="1" max="80" placeholder="80" aria-label="Maximum level"></div></fieldset>
    <button type="submit" class="table-button">Apply filters</button>
    <button type="reset" class="table-reset">Clear</button>
</form>
